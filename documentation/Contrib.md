# Guide de contribution - Projet Ticket

## Équipe

| Membre | Rôle principal |
|------------------------------|------------------------------------------------------------------|
| Mathieu Piris                | Backend Laravel, setup Docker, configuration BDD                 |
| Jean-Baptiste Noblet         | Frontend Vue.js, wireframes, direction graphique, beta-testeurs  |
| Les deux                     | Modélisation BDD, logique métier générale                        |
|------------------------------|------------------------------------------------------------------|

## Organisation

Le projet suit une approche Agile légère. L'équipe travaille majoritairement
en présentiel, les échanges à distance se font via **Discord**.
Toutes les modifications sont committées directement sur `main` avec un historique
traçable par contributeur visible sur GitHub (Insights > Contributors).

## Convention de commits

Pas de convention stricte imposée. Les messages suivent le sens de la modification :

- Fonctionnalité : `Ajout [feature]` ou `Add [feature]`
- Correction : `Fix [description]`
- Configuration : `Configuration [outil]`
- Mise à jour : `Update [description]`

Exemples tirés du projet :
- `Ajout du Marché Noir des Organes (solde = 0)`
- `Fix scratch coordinate scaling on all ticket components`
- `Configuration Docker MySQL + MongoDB`

## Suivi des tâches et bugs

Les bugs et ajustements sont traités en temps réel en présentiel ou via Discord.
Le suivi de l'avancement est assuré par l'historique des commits sur GitHub.
Les correctifs sont généralement simples et patchés directement au moment
où ils sont identifiés.

## Tests

Les tests PHPUnit sont exécutés automatiquement à chaque push sur `main`
via GitHub Actions. Pour les lancer manuellement :

```bash
php artisan test
```

## Stack technique

- **Backend** : Laravel 13, PHP 8.4, Spatie Permission
- **Frontend** : Vue.js 3, Vite
- **BDD** : SQLite (local) / MySQL + MongoDB (Docker)
- **CI/CD** : GitHub Actions (`.github/workflows/ci.yml`)

## Lancer le projet

Voir le [README](./README.md) pour les instructions complètes.