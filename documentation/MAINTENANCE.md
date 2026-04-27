# Procédures de Maintenance - Projet Ticket

## Types de maintenance

### Maintenance corrective
Correction de bugs ou anomalies identifiés en production.

### Maintenance évolutive
Ajout de nouvelles fonctionnalités ou amélioration de l'existant.

---

## Maintenance corrective

### 1. Identifier le problème

- Consulter les logs Laravel : `storage/logs/laravel.log`
- Consulter les logs Docker : `docker-compose logs -f app`
- Reproduire le bug en local avant de corriger

### 2. Corriger

```bash
# Appliquer la correction, puis committer sur main
git add .
git commit -m "fix: description du correctif"
git push origin main
```

### 3. Vérifier

```bash
# Lancer les tests automatisés
php artisan test

# Vérifier que les tests passent avant de merger sur main
```

### 4. Déployer

Voir la section [Déploiement](#déploiement) ci-dessous.

---

## Maintenance évolutive

### 1. Planifier

- Définir la fonctionnalité et ses impacts (BDD, API, frontend)

### 2. Développer

```bash
# Migrations BDD si nécessaire
php artisan make:migration nom_migration
php artisan migrate

# Modèles / Controllers
php artisan make:model NomModel
php artisan make:controller NomController

# Builder le frontend après modification des .vue
npm run build
```

### 3. Tester

```bash
php artisan test
```

### 4. Déployer

```bash
git add .
git commit -m "feat: description de la fonctionnalité"
git push origin main
```

---

## Déploiement

### Mise à jour en production (Docker)

```bash
# 1. Récupérer les dernières modifications
git pull origin main

# 2. Reconstruire les containers
docker-compose up --build -d

# 3. Vérifier que tout tourne
docker-compose ps
docker-compose logs -f app
```

### Mise à jour en local (SQLite)

```bash
# 1. Récupérer les dernières modifications
git pull origin main

# 2. Mettre à jour les dépendances PHP si nécessaire
composer install

# 3. Mettre à jour les dépendances Node.js si nécessaire
npm install

# 4. Appliquer les nouvelles migrations
php artisan migrate

# 5. Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 6. Rebuilder le frontend
npm run build
```

### Mise à jour des dépendances

```bash
# Mettre à jour les packages PHP
composer update

# Mettre à jour les packages Node.js
npm update

# Toujours relancer les tests après une mise à jour
php artisan test
```

---

## Sauvegardes

### Base de données SQLite

```bash
cp database/database.sqlite database/database.sqlite.bak
```

### Base de données MySQL (Docker)

```bash
docker exec projet-ticket-db mysqldump -u laravel -p laravel > backup_$(date +%Y%m%d).sql
```

---

## CI/CD

Les tests sont exécutés automatiquement à chaque push sur `main` via GitHub Actions (`.github/workflows/ci.yml`).

Un push ne doit être mergé sur `main` que si les tests passent.

---

## Contacts

En cas de problème non résolu, consulter l'historique des commits sur GitHub
ou contacter l'équipe via Discord.
