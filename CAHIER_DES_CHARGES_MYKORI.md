# 📘 Cahier des Charges Fonctionnel : Solution MyKori

## 📑 Introduction
Ce document présente de manière simplifiée l'ensemble des fonctionnalités de la plateforme **MyKori**. Il sert de référence pour comprendre le fonctionnement du système, les rôles des utilisateurs et les règles de gestion financière appliquées.

---

## 🚀 1. Vision et Objectifs
MyKori est une solution digitale de gestion d'actifs financiers conçue pour automatiser le suivi des investissements, sécuriser les flux transactionnels et offrir une transparence totale aux investisseurs.

**Les piliers de la solution :**
*   **Automatisation :** Calcul en temps réel des intérêts et des plus-values.
*   **Sécurité :** Workflow de validation multi-acteurs pour chaque transaction.
*   **Accessibilité :** Dashboards dédiés pour chaque profil (Client, Gestionnaire, Direction).
*   **Reporting :** Génération instantanée de relevés de compte officiels.

---

## 👥 2. Profils Utilisateurs (Rôles)
Le système repose sur une gestion de permissions segmentée :

1.  **Client (User) :**
    *   Consultation de son patrimoine financier.
    *   Suivi graphique des gains (Hebdomadaires / Mensuels).
    *   Téléchargement des relevés de portefeuille au format PDF.
2.  **Asset Manager (KAM/Manager) :**
    *   Gestion de son portefeuille de clients affectés.
    *   Initiation de nouvelles souscriptions ou rachats.
    *   Suivi de la performance globale de son périmètre.
3.  **Compliance (Conformité) :**
    *   Vérification réglementaire des transactions initiées.
    *   Validation "Niveau 1" des opérations (KYC, origine des fonds).
4.  **Backoffice :**
    *   Validation technique et financière finale.
    *   Mise à jour des Valeurs Liquidatives (VL).
    *   Exécution des mouvements complexes (Capitalisation, Rachats).
5.  **Direction Générale (DG) :**
    *   Pilotage stratégique via des indicateurs consolidés (AUM global).
    *   Suivi de l'activité du réseau d'Asset Managers.
    *   Audit de la rentabilité globale du fonds.

---

## 💰 3. Les Produits Financiers
MyKori gère deux types de placements avec des règles de calcul spécifiques :

### A. FCP (Fonds Commun de Placement)
*   **Mécanique :** Acquisition de "Parts" dont la valeur (VL) fluctue selon le marché.
*   **Calcul :** `Valorisation = Nombre de parts × VL du jour`.
*   **Performance :** Mesurée par la différence entre le coût d'acquisition (PRU) et la valeur actuelle.

### B. PMG (Porte-Monnaie de Gestion)
*   **Mécanique :** Placement à taux d'intérêt défini avec une date d'échéance.
*   **Calcul :** Calcul linéaire au prorata temporis (Base 360 jours).
*   **Capitalisation :** Possibilité de réinvestir automatiquement les intérêts à chaque date anniversaire du contrat pour maximiser les revenus futurs.

---

## ⚙️ 4. Workflow Opérationnel (Cycle de Vie)
Pour garantir une sécurité maximale, aucune transaction n'est immédiate. Elle suit le parcours suivant :

1.  **Saisie :** L'Asset Manager saisit les détails du placement.
2.  **Contrôle Compliance :** Analyse de la conformité du dossier.
3.  **Validation Backoffice :** Confirmation de la réception effective des fonds.
4.  **Activation :** Une fois validée par le Backoffice, la transaction passe au statut "Succès" et commence à générer des gains de manière automatique.

---

## 📊 5. Reporting et Outils d'Aide à la Décision
*   **Dashboards Interactifs :** Graphiques de répartition des actifs (Asset Allocation).
*   **Historique des Flux :** Journal complet de tous les versements, retraits et intérêts payés.
*   **Alertes Échéances :** Notifications pour les contrats PMG arrivant à terme, permettant une relance commerciale proactive.
*   **Relevés PDF :** Génération de documents certifiés reflétant l'état du patrimoine à une date donnée.

---

## 🔒 6. Sécurité Technique
*   **Double Authentification (OTP) :** Sécurisation des accès aux comptes.
*   **Journal d'Audit :** Traçabilité complète (qui a fait quoi et quand).
*   **Ségrégation des Tâches :** Impossibilité pour un seul acteur de valider ses propres opérations de bout en bout.

---
*Fin du document - Dernière mise à jour : 14 Avril 2026*
