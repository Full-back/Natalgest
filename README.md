# Module Gestion des Comptes Utilisateurs — Natalgest

Ce module implémente la **connexion**, la **création/gestion des comptes** (par l'Admin)
et le **contrôle des droits d'accès (RBAC)**, conformément aux sections 3 et 4.1 du
cahier des charges.

## Installation (WAMP)

1. Copier le dossier `cpn_cpon_auth` dans `htdocs` (XAMPP) ou `www` (WAMP).
2. Démarrer Apache et MySQL depuis le panneau de contrôle.
3. Importer la base de données :
   - Ouvrir phpMyAdmin (`http://localhost/phpmyadmin`)
   - Créer une nouvelle requête SQL et exécuter le contenu de `sql/schema.sql`
     (ou `Importer` > sélectionner le fichier).
4. Vérifier les identifiants de connexion dans `config/database.php`
   (par défaut : utilisateur `root`, mot de passe vide — standard XAMPP).
5. Accéder à l'application : `http://localhost/cpn_cpon_auth/login.php`

## Compte administrateur par défaut

| Email                   | Mot de passe |
|--------------------------|--------------|
| admin@cpn-cpon.local     | Admin@2026   |

⚠️ À changer immédiatement après la première connexion en environnement réel.

## Structure du module

```
cpn_cpon_auth/
├── config/
│   └── database.php          # Connexion MySQLi
├── includes/
│   ├── auth.php               # requireLogin(), requireRole(), redirectToDashboard()
│   ├── header.php             # En-tête HTML + navbar commune
│   └── footer.php             # Pied de page commun
├── admin/
│   ├── gestion_utilisateurs.php   # Liste des comptes + actions
│   ├── creer_utilisateur.php      # Formulaire de création (Admin uniquement)
│   ├── modifier_utilisateur.php   # Formulaire de modification
│   └── activer_desactiver.php     # Activation / désactivation (soft-delete)
├── assets/css/style.css       # Styles additionnels
├── sql/schema.sql             # Structure de la table users + compte admin par défaut
├── login.php                  # Page de connexion
├── logout.php                 # Déconnexion
└── accueil.php                # Page d'accueil neutre après connexion (PAS un dashboard métier)
```

Ce module se limite volontairement à la **connexion, la gestion des comptes et les
droits d'accès**. Il ne contient aucun tableau de bord métier : `accueil.php` est un
simple point d'entrée qui confirme que l'authentification et le RBAC fonctionnent, et
que le reste de l'équipe pourra remplacer ou enrichir avec les vrais espaces de travail
(gestion des patientes, consultations, statistiques...).

## Fonctionnement du contrôle d'accès

Chaque page protégée commence par :

```php
require_once __DIR__ . '/includes/auth.php';
requireRole('admin');       
```

- `requireLogin()` vérifie la session et gère la déconnexion automatique après
  15 minutes d'inactivité (section 7.2 du cahier des charges).
- `requireRole()` bloque l'accès (403) si le rôle de l'utilisateur connecté ne
  correspond pas à celui attendu par la page — c'est la mise en œuvre technique
  de la matrice RBAC de la section 3.

## Sécurité implémentée

- Mots de passe hachés avec `password_hash()` (BCRYPT) — jamais stockés en clair.
- Requêtes préparées MySQLi partout — protection contre les injections SQL.
- Échappement des sorties avec `htmlspecialchars()` — protection XSS.
- Régénération de l'ID de session à la connexion — protection contre le session fixation.
- Déconnexion automatique après inactivité.
- Désactivation de compte (soft-delete) plutôt que suppression — traçabilité conservée.
- Un compte `admin` ne peut pas être désactivé depuis l'interface (garde-fou).

Les futurs tableaux de bord par rôle (Réceptionniste, Docteur, Admin) devront :
1. Inclure `includes/auth.php` et appeler `requireRole('...')` en haut de script.
2. Utiliser `$_SESSION['user_id']`, `$_SESSION['user_nom']` et `$_SESSION['user_role']`
   pour personnaliser l'affichage.
3. Éventuellement rediriger depuis `accueil.php` vers leur propre dashboard une fois développé.

Modules à brancher par le reste de l'équipe :
- Module Gestion des Patientes (Réceptionniste)
- Module Prénatal / Postnatal (Docteur)
- Statistiques d'activité (Admin)
