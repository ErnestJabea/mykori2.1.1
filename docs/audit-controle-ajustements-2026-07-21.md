# Audit technique - module Controle et ajustements

Date d'audit : 21/07/2026  
Projet : myKORI / Laravel 8  
Objet : verifier si le module de controle, corrections, versioning et audit des releves peut etre active en securite.

## Conclusion directe

Le module "Controle et ajustements" existe deja partiellement dans le code, mais il ne doit pas etre active tel quel en production.

Il apporte une base utile : ecrans de controle, workflow demande/validation/rejet, pieces justificatives privees, journal d'audit, regle des 4 yeux. Mais les garanties metier et securite ne sont pas suffisantes pour garantir la veracite des releves.

Les blocages principaux identifies lors de l'audit initial etaient :

1. Les policies sont ecrites mais non enregistrees dans `AuthServiceProvider`.
2. Les routes du module n'appliquent pas `permission:view_control_adjustments`.
3. La validation applique directement une modification dynamique sur `Transaction` ou `FinancialMovement` sans revalider la liste blanche.
4. La correction ne relance pas un vrai recalcul centralise du releve et ne regenere pas le PDF final.
5. La simulation FCP n'utilise pas la logique officielle basee sur `fcp_movements` et les VL historiques.
6. Les migrations ne posent pas assez de contraintes base de donnees pour un module sensible.
7. Les tests du module utilisent potentiellement la base locale existante et ne sont pas isoles.

## Correctifs appliques apres audit

Les corrections prioritaires suivantes ont ete appliquees apres ce constat :

1. `FEATURE_CONTROL_ADJUSTMENTS` est desormais ferme par defaut dans `config/app.php`.
2. Le middleware `EnsureControlAdjustmentEnabled` ferme aussi l'acces par defaut si la config est absente.
3. Les routes `backoffice/control-adjustments` exigent maintenant `permission:view_control_adjustments`.
4. `StatementCorrectionPolicy` est enregistree dans `AuthServiceProvider`.
5. `ControlAdjustmentController` appelle explicitement les autorisations Laravel sur les actions sensibles.
6. `AdjustmentService` revalide cote serveur :
   - l'entite cible ;
   - l'appartenance au client ;
   - l'appartenance au produit ;
   - la liste blanche du champ ;
   - le type de nouvelle valeur.
7. La validation d'une correction ne fait plus d'affectation dynamique sans controle prealable.
8. La simulation FCP utilise maintenant le solde des mouvements `fcp_movements` et la derniere VL historique disponible avant la date cible, avec fallback sur les donnees de transaction si aucun mouvement n'existe.
9. Les migrations du module ajoutent maintenant des cles etrangeres, des index de cible et une empreinte `payload_sha256_hash`.
10. La validation d'une correction cree maintenant une nouvelle version calculee via `StatementVersioningService`.
11. Le payload calcule du releve est hashe en SHA-256 pour prouver qu'il n'a pas change.
12. Les tests du module utilisent `RefreshDatabase` et SQLite en memoire via `phpunit.xml`.
13. Une validation de correction genere maintenant un PDF technique de version calculee sur le disque prive.
14. Le hash SHA-256 du PDF genere est stocke dans `statement_versions.sha256_hash`.
15. Une migration officielle `auth_code` a ete ajoutee pour le middleware OTP.
16. Les PDF officiels generes par l'envoi manuel des releves sont maintenant enregistres dans `statement_versions` avec statut `Envoye`, chemin, hash PDF et journal d'audit.
17. Le module de correction utilise maintenant le generateur officiel PMG/FCP quand le produit corrige permet d'identifier le type de releve.

Ces correctifs reduisent le risque d'acces non autorise et de modification arbitraire, mais ils ne terminent pas encore le module de controle/releve.

Risques encore ouverts :

- un fallback PDF technique existe encore uniquement si le produit corrige ne permet pas d'identifier un releve officiel PMG/FCP ;
- pour les FCP, il faut encore definir si une correction doit modifier la transaction source, le mouvement FCP, ou produire une contre-ecriture d'ajustement.
- le modele officiel de releve client doit encore etre raccorde au versioning si l'on veut envoyer exactement le document versionne au client.

Verification executee :

`LOG_CHANNEL=stderr php -d extension_dir=C:\MAMP\bin\php\php8.1.0\ext -d extension=mbstring -d extension=openssl -d extension=pdo_sqlite -d extension=sqlite3 vendor\bin\phpunit --filter ControlAdjustmentModuleTest`

Resultat initial : `OK (6 tests, 18 assertions)`.

Apres ajout du PDF de version et du hash PDF :

`LOG_CHANNEL=stderr php -d extension_dir=C:\MAMP\bin\php\php8.1.0\ext -d extension=mbstring -d extension=openssl -d extension=pdo_sqlite -d extension=sqlite3 vendor\bin\phpunit --filter ControlAdjustmentModuleTest`

Resultat : `OK (6 tests, 21 assertions)`.

Apres ajout de la migration `auth_code` :

`LOG_CHANNEL=stderr php -d extension_dir=C:\MAMP\bin\php\php8.1.0\ext -d extension=mbstring -d extension=openssl -d extension=pdo_mysql artisan migrate --force`

Resultat : `Migrated: 2026_07_22_000001_create_auth_code_table`.

Verification migrations :

`LOG_CHANNEL=stderr php -d extension_dir=C:\MAMP\bin\php\php8.1.0\ext -d extension=mbstring -d extension=openssl -d extension=pdo_mysql artisan migrate --pretend`

Resultat : `Nothing to migrate`.

Apres raccordement des PDF officiellement envoyes au versioning :

`LOG_CHANNEL=stderr php -d extension_dir=C:\MAMP\bin\php\php8.1.0\ext -d extension=mbstring -d extension=openssl -d extension=pdo_sqlite -d extension=sqlite3 vendor\bin\phpunit --filter ControlAdjustmentModuleTest`

Resultat : `OK (7 tests, 26 assertions)`.

Apres raccordement du module de correction au generateur officiel :

`LOG_CHANNEL=stderr php -d extension_dir=C:\MAMP\bin\php\php8.1.0\ext -d extension=mbstring -d extension=openssl -d extension=pdo_sqlite -d extension=sqlite3 vendor\bin\phpunit --filter ControlAdjustmentModuleTest`

Resultat : `OK (7 tests, 28 assertions)`.

## Etat actuel du depot

Le depot contient des modifications non commitees avant correction :

- `app/Http/Controllers/ControlAdjustmentController.php`
- `app/Services/AdjustmentService.php`
- `app/Models/StatementVersion.php`
- `app/Models/StatementCorrection.php`
- `app/Models/StatementAuditLog.php`
- `app/Policies/StatementCorrectionPolicy.php`
- migrations `statement_versions`, `statement_corrections`, `statement_audit_logs`
- vues `resources/views/control_adjustments/*`
- test `tests/Feature/ControlAdjustmentModuleTest.php`
- modifications de `routes/web.php`, `config/app.php`, `config/filesystems.php`, `AccessControlService`, `Kernel`

Ces changements doivent etre consideres comme un brouillon avance, pas comme une version prete a livrer.

## Architecture fonctionnelle observee

### Tables ajoutees

`statement_versions`

- stocke une version de releve par client/produit/periode ;
- contient `pdf_path`, `sha256_hash`, `summary_payload`, statut, version precedente ;
- manque de contraintes fortes : unicite client/produit/periode/version, foreign keys vers `users`, `products`, createur, validateur.

`statement_corrections`

- stocke les demandes de correction ;
- contient cible, champ modifie, ancienne/nouvelle valeur, piece justificative, simulation, statut, operateur, controleur ;
- manque de contraintes fortes sur les statuts, les cibles, les utilisateurs et les produits.

`statement_audit_logs`

- stocke le journal d'audit ;
- le modele interdit update/delete via events Eloquent ;
- ce n'est pas suffisant seul, car une modification SQL directe peut contourner Eloquent.

### Workflow implemente

1. Un operateur ouvre une fiche client.
2. Il demande une correction sur un champ affiche dans une liste blanche cote interface.
3. Une simulation d'impact est calculee.
4. La demande passe en statut `A_controler`.
5. Un autre utilisateur valide ou rejette.
6. En validation, la source est modifiee puis un log est cree.

Le principe est bon, mais l'application technique est incomplete.

## Calculs et veracite des donnees

### PMG

Le module utilise `ProductController::calculatePMGValorization()` pour les PMG.

C'est coherent avec une partie importante du projet, car cette methode tient compte :

- de la date de valeur ;
- de la date d'echeance ;
- de la logique 30/360 ;
- des mouvements de capitalisation ;
- des rachats partiels ;
- des liquidites interets/capital ;
- des paiements deja effectues.

Risque restant : plusieurs moteurs de calcul coexistent encore dans le projet (`ProductController`, `InvestmentService`, `GainCalculationService`, anciens controllers backup). Tant qu'une seule source officielle de calcul n'est pas imposee, il restera possible d'avoir des ecarts entre dashboard, detail client, releve PDF et module de controle.

### FCP

Le module actuel calcule les FCP ainsi :

`nb_part * (product.latest_vl ?? vl_buy)`

Ce n'est pas acceptable pour un controle fiable.

Le projet possede deja une logique plus robuste via `fcp_movements`, `nb_parts_change`, `vl_applied`, `asset_values`, et `FinancialDecimal`. Pour les FCP, la valorisation doit utiliser toutes les decimales des parts et de la VL, puis arrondir seulement a l'affichage.

Conclusion : la simulation FCP du module d'ajustement doit etre remplacee par le moteur officiel base sur les mouvements FCP.

## Securite et habilitations

### Points positifs

- Le middleware `EnsureControlAdjustmentEnabled` bloque les clients (`role_id = 2`).
- La regle des 4 yeux est presente dans le service : l'operateur ne peut pas valider sa propre correction.
- Les pieces justificatives sont stockees sur un disque `private`.
- Les routes sont sous `auth` et `otp.verified`.

### Points bloquants

#### 1. Policy non enregistree

`StatementCorrectionPolicy` existe, mais `AuthServiceProvider` ne mappe aucun modele vers cette policy.

Consequence : les methodes `viewAny`, `create`, `validateOrReject`, `viewAudit` ne protegent rien tant que le controleur ne les appelle pas explicitement ou que la policy n'est pas enregistree.

#### 2. Middleware de permission absent sur les routes

Les routes utilisent :

`auth`, `otp.verified`, `control.adjustments`

Elles devraient au minimum utiliser aussi :

`permission:view_control_adjustments`

Sinon tout utilisateur interne non client pourrait atteindre le module si le feature flag est actif.

#### 3. Validation dynamique dangereuse

Dans `AdjustmentService::validateCorrection()`, le code fait :

`$transaction->{$correction->field_name} = $correction->new_value;`

ou :

`$movement->{$correction->field_name} = $correction->new_value;`

Le champ vient de la correction stockee. La liste blanche est visible dans l'interface, mais elle n'est pas revalidee strictement au moment critique.

Ce point est critique. Une correction mal creee ou inseree pourrait modifier un champ non autorise.

#### 4. Appending audit insuffisant

Le modele `StatementAuditLog` bloque update/delete via Eloquent, mais il faut renforcer :

- droits DB limites pour l'application ;
- absence d'ecran de suppression ;
- eventuellement triggers SQL ou table d'audit separee selon l'environnement cible ;
- hash chaine ou signature des logs sensibles si on veut une preuve forte.

## Versioning des releves

Le modele `StatementVersion` est une bonne base, mais le versioning n'est pas complet.

Aujourd'hui, quand une correction est validee :

- une nouvelle version peut etre creee si la version precedente est immuable ;
- l'ancienne version passe en `Remplace`.

Mais il manque :

- regeneration effective du PDF ;
- recalcul officiel du payload ;
- calcul du hash SHA-256 du PDF/payload ;
- liaison claire entre correction validee, ancienne version, nouvelle version ;
- statut metier complet : `Brouillon`, `Calcule`, `A_controler`, `Valide`, `Envoye`, `Remplace`, `Annule`.

Sans cela, on ne peut pas dire qu'un releve corrige est juridiquement ou operationnellement tracable.

## Tests

Les fichiers PHP inspectes passent `php -l`.

Fichiers verifies :

- `app/Http/Controllers/ControlAdjustmentController.php`
- `app/Services/AdjustmentService.php`
- `app/Http/Middleware/EnsureControlAdjustmentEnabled.php`
- `app/Models/StatementCorrection.php`
- `app/Models/StatementVersion.php`
- `app/Models/StatementAuditLog.php`
- `app/Policies/StatementCorrectionPolicy.php`

`php artisan route:list --path=control-adjustments` n'a pas pu etre execute car Laravel n'arrive pas a ecrire dans `storage/logs/laravel.log` :

`Permission denied`

Le test `ControlAdjustmentModuleTest` n'est pas assez sur :

- il ne semble pas utiliser `RefreshDatabase` ;
- il cree/modifie des donnees dans la base courante ;
- `phpunit.xml` garde SQLite en memoire commente ;
- les tests peuvent polluer la base locale.

Il ne faut pas executer ces tests sur la base de travail sans isoler l'environnement de test.

## Corrections prioritaires recommandees

### Priorite 1 - Securiser l'acces

1. Mettre `FEATURE_CONTROL_ADJUSTMENTS=false` par defaut.
2. Ajouter `permission:view_control_adjustments` aux routes.
3. Enregistrer `StatementCorrectionPolicy` dans `AuthServiceProvider`.
4. Appeler explicitement `authorize()` dans le controleur :
   - `viewAny` pour index/historique ;
   - `create` pour store/simulate ;
   - `validateOrReject` pour validate/reject ;
   - verification specifique pour downloadProof.

### Priorite 2 - Verrouiller les champs modifiables

1. Revalider la liste blanche dans `simulateCorrection()`.
2. Revalider la meme liste blanche dans `requestCorrection()`.
3. Revalider encore dans `validateCorrection()`.
4. Verifier que la cible appartient bien au client et au produit declares.
5. Interdire les champs calcules, statuts workflow, `user_id`, `product_id`, `id`, timestamps.

### Priorite 3 - Centraliser le recalcul

1. Extraire un service unique de valorisation :
   - PMG : moteur 30/360 + liquidites + echeance + paiements.
   - FCP : mouvements FCP + VL historiques + toutes decimales.
2. Faire utiliser ce service par :
   - dashboard ;
   - detail client ;
   - releves PDF ;
   - module de controle ;
   - simulations.

### Priorite 4 - Completer le versioning

1. Une correction validee doit produire une nouvelle version calculee.
2. Le PDF doit etre regenere.
3. Le hash SHA-256 doit etre stocke.
4. L'ancienne version doit rester telechargeable comme archive.
5. La nouvelle version doit avoir une piste claire : correction source, operateur, controleur, date, motif, piece justificative.

### Priorite 5 - Renforcer la base

Ajouter :

- foreign keys vers `users`, `products`, `statement_versions` ;
- index utiles ;
- contraintes d'unicite sur `(user_id, product_id, period_name, version_number)` ;
- statuts normalises ;
- types de cibles limites ;
- eventuellement table dediee pour les preuves et documents.

### Priorite 6 - Tests propres

1. Activer une base SQLite `:memory:` ou une base MySQL de test separee.
2. Ajouter `RefreshDatabase`.
3. Tester :
   - client interdit ;
   - role interne non habilite interdit ;
   - create autorise seulement aux roles prevus ;
   - validation par meme utilisateur interdite ;
   - champ hors liste blanche refuse ;
   - cible n'appartenant pas au client refusee ;
   - recalcul PMG avec echeance ;
   - recalcul FCP avec decimales ;
   - nouvelle version de releve generee apres validation.

## Decision recommandee

Ne pas pousser le module tel quel en production.

La prochaine etape saine est de transformer le brouillon actuel en module securise minimal :

1. feature flag desactive par defaut ;
2. permissions et policies effectives ;
3. liste blanche imposee cote serveur ;
4. verification d'appartenance client/produit/cible ;
5. simulation FCP remplacee par le moteur officiel ;
6. tests isoles ;
7. ensuite seulement, activation progressive.

## Remarque importante

La securite parfaite n'existe pas. L'objectif realiste est une securite defendable : controles d'acces stricts, validations serveur, audit non modifiable par l'application, recalcul reproductible, preuves attachees, separation des roles, tests automatises et environnement de test isole.
