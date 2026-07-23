# Audit de toutes les transactions au 23/07/2026

## Perimetre et methode

- 101 transactions principales, dont 98 au statut Succes.
- 14 transactions supplementaires, dont 10 au statut Succes.
- 84 positions PMG validees: 83 principales et 1 supplementaire.
- 25 mouvements FCP, representant 15 positions actives.
- Audit strictement en lecture seule.
- Recalcul PMG independant en base 30/360.
- Recalcul FCP avec `montant net / VL source exacte`, puis valorisation avec toutes les decimales disponibles.
- Controle des identites de capital, des statuts, des dates, des liquidites et du rapprochement transaction/mouvement.

## Conclusion

Les additions du tableau de bord sont coherentes avec les donnees actuellement retenues comme valides:

- PMG clients actifs: 6 550 341 054,00 XAF.
- FCP actifs: 228 455 117,45 XAF.
- Total actif clients: 6 778 796 171,45 XAF.

Ces montants ne peuvent toutefois pas etre declares integralement certifies. Le calcul simple PMG est mathematiquement correct, mais les donnees contractuelles et le registre financier sont incomplets ou contradictoires sur plusieurs operations.

## PMG

### Calculs valides

- Les 36 PMG sans aucun mouvement financier ont tous ete recalcules independamment.
- Resultat: 36 sur 36 identiques au calcul applicatif en base 30/360.
- Aucun ecart de formule simple n'a ete detecte.

### Modalites contractuelles absentes

Les 84 positions PMG ont `interest_management = NULL`. Le systeme applique donc la capitalisation annuelle par defaut, sans pouvoir prouver si le contrat prevoyait:

- paiement a echeance;
- paiement annuel a la date anniversaire;
- capitalisation jusqu'a echeance;
- interets precomptes;
- paiement mensuel exceptionnel.

### PMG echus sans liquidite

Les 14 PMG suivants sont echus sans ecriture `liquidite_capital` ni `paiement_capital`:

| ID | Client | Reference | Echeance | Valeur a echeance XAF |
|---:|---|---|---:|---:|
| 16 | HORUS INVESTMENT CAPITAL | Kori-672b372c7ea21 | 31/01/2025 | 2 334 500 000 |
| 72 | AFRICA BRIGHT ASSET MANAGEMENT SA | Kori-699c6ab31db6b | 01/06/2026 | 516 250 000 |
| 42 | AFRICA BRIGHT ASSET MANAGEMENT SA | Kori-6863da686e21c | 01/12/2025 | 514 534 722 |
| 5 | Client supprime, user_id 16 | Kori-66ac7d348f6c9 | 05/01/2026 | 451 987 600 |
| 7 | INNOV'ENT SARL | Kori-66afadc227856 | 15/07/2026 | 75 816 000 |
| 9 | ASSURANCES DU CAMEROUN VIE | Kori-66afb139a9e56 | 05/01/2026 | 56 498 450 |
| 41 | MANJANJA EDIMO Daniel | Kori-6863da1ac04cc | 16/12/2025 | 52 000 000 |
| 3 | EKANI NDI Sandrine | Kori-6697c364e7bb8 | 15/01/2025 | 18 900 000 |
| 15 | NGUEPI MEKUE Vanessa Nathalie | Kori-66fbdc40ccbd8 | 17/10/2025 | 10 843 039 |
| 6 | NJILIE Franck | Kori-66af8ea3def54 | 19/12/2024 | 5 250 000 |
| 11 | MAMMA MODI Nahima | Kori-66b21234324e8 | 19/12/2024 | 5 250 000 |
| 29 | NGASSA TCHAPNDA Dimitri | Kori-67ec0c4948c12 | 17/03/2026 | 3 465 000 |
| 39 | SADJO VROUA KAMGA Sarah | Kori-683dd137e682e | 10/04/2026 | 2 415 000 |
| 12 | FOMETHE Patrick Alexandre | Kori-66b2593232c63 | 15/02/2025 | 2 080 000 |

Total a justifier en liquidite ou paiement: **4 049 789 811 XAF**, compose de:

- principal reconstitue: 3 915 933 681 XAF;
- interets calcules: 133 856 130 XAF.

### Capitalisations incoherentes

| Mouvement | Client | Attendu selon date inscrite | Enregistre | Ecart XAF | Diagnostic |
|---:|---|---:|---:|---:|---|
| 56 | HORUS INVESTMENT CAPITAL | 138 000 000 | 34 500 000 | 103 500 000 | Montant correspondant a 3 mois, mais mouvement date un an apres le debut et apres echeance. |
| 57 | DJOUMESSI NIMPA JEAN PAUL | 13 500 000 | 14 715 000 | 1 215 000 | Montant incompatible avec le capital avant, le taux et la periode. |
| 68 | MANJANJA EDIMO Daniel | 4 000 000 | 2 000 000 | 2 000 000 | Montant correspondant a 6 mois, mais mouvement date un an apres le debut et apres echeance. |
| 69 | AFRICA BRIGHT ASSET MANAGEMENT SA | 32 500 000 | 14 534 722 | 17 965 278 | Montant correspondant a la courte periode contractuelle, mais mouvement inscrit apres echeance. |
| 7 | Admin | 100 000 | 99 444 | 556 | Ecart de calcul. |
| 8 | Admin | 109 944 | 109 028 | 916 | Ecart de calcul. |

Trois de ces six anomalies sont principalement des erreurs de `date_operation`: HORUS, MANJANJA et AFRICA BRIGHT. La valeur enregistree correspond approximativement a l'interet jusqu'a echeance, mais l'ecriture est datee plusieurs mois plus tard.

### Registre PMG incomplet ou contradictoire

- 36 PMG n'ont aucun registre financier.
- 34 autres ont des mouvements mais pas de `souscription_initiale`.
- Mouvement 4, ELONGUE MOUSSOUME: interet de 2 000 000 XAF, mais capital avant et apres identiques.
- Mouvements 9 et 40, ELONGUE MOUSSOUME: chaine de capital du meme jour incoherente.
- Mouvement 43, AROCK OBEN: chaine de capital du meme jour incoherente.
- Deux transactions PMG pointent vers des clients supprimes ou inexistants: IDs 5 et 53.

## FCP

### Resultat global

- Valorisation avec parts actuellement stockees: 228 455 117,45 XAF.
- Valorisation apres reconstruction avec la VL source exacte: 228 455 117,42 XAF.
- Ecart courant: -0,03 XAF.
- Somme des ecarts `montant - parts x VL` sur les 25 mouvements: 0,14 XAF.

L'impact actuel est faible, mais la base ne respecte pas l'exigence de conservation de toutes les decimales:

- `fcp_movements.vl_applied` est limite a `DECIMAL(15,4)`;
- `fcp_movements.nb_parts_change` est limite a `DECIMAL(15,6)`;
- les VL sources contiennent jusqu'a plus de dix decimales.

### Anomalies FCP

- 23 mouvements sur 25 ont une VL appliquee tronquee par rapport a la VL source.
- Mouvement 47739, KORI ASSET MANAGEMENT: ecart de 0,055609 XAF.
- Mouvement 47751, EPEE MOUTO Carole Germaine: ecart de 0,011893 XAF.
- 20 mouvements ont un `nb_parts_total` faux, generalement laisse a zero.
- Toutes les souscriptions FCP validees possedent exactement un mouvement source.
- Aucun solde negatif de parts n'a ete detecte.

## Statuts et piste d'audit

- Transaction 31, AROCK OBEN: statut `En attente`, mais 3 mouvements PMG existent.
- Transaction 103, NSIA VIE ASSURANCES: statut `Refuse`, mais une souscription initiale de 100 000 000 XAF existe.
- Ces mouvements sont exclus des portefeuilles valides, ce qui est logique au regard du statut, mais le registre est contradictoire.
- 107 operations au statut Succes ne portent pas la preuve complete des validations Compliance, Backoffice et DG. La majorite semble provenir des donnees historiques anterieures au workflow actuel.

## Corrections recommandees

1. Renseigner et faire valider la modalite d'interets des 84 PMG avant toute generation corrective.
2. Rattacher ou cloturer les deux transactions orphelines.
3. Arbitrer les statuts AROCK OBEN et NSIA VIE ASSURANCES avant de toucher leurs mouvements.
4. Corriger les dates des trois capitalisations post-echeance et le calcul DJOUMESSI avec une piste d'audit.
5. Generer les liquidites des 14 PMG echus seulement apres validation du capital et des interets par le Backoffice.
6. Elargir les colonnes FCP a au moins 16 decimales et reconstruire les parts depuis la VL source, avec apercu avant application.
7. Recalculer `nb_parts_total` dans l'ordre date/id.
8. Empecher la creation de mouvements sur une transaction non validee et rendre la modalite PMG obligatoire.

## Fichiers produits

- `storage/app/audits/transaction-audit-2026-07-23-issues.csv`
- `storage/app/audits/transaction-audit-2026-07-23-summary.json`
- Auditeur reutilisable: `scratch/audit_all_transactions.php`
