# Projet Ticket - Jeu de Grattage

Jeu de tickets à gratter style FDJ développé avec Laravel + Vue.js pour un projet BTS.

## Fonctionnalités

- Tickets à gratter (Métro, Bus, Train, Loterie) avec différents thèmes FDJ
- Système d'authentification avec rôles (admin/joueur)
- Solde persistant en base de données
- Classement des joueurs
- Panel d'administration avec statistiques
- "Marché Noir des Organes" - machine à sous spéciale quand le solde atteint 0$
- Logging des actions avec MongoDB (optionnel)

---

## Installation

### Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- Git

---

## Option 1 : Installation locale (SQLite)

```bash
# 1. Installer les dépendances système (Ubuntu/Debian)
sudo apt update
sudo apt install -y php php-cli php-mbstring php-xml php-curl php-sqlite3 php-zip unzip curl git nodejs npm

# 2. Installer Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 3. Cloner le projet
git clone https://github.com/Valaraukar56/projet-ticket.git
cd projet-ticket

# 4. Installer les dépendances PHP
composer install

# 5. Installer les dépendances Node.js
npm install

# 6. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 7. Créer la base SQLite
touch database/database.sqlite

# 8. Vérifier la configuration .env
# Ouvrir .env et s'assurer que :
# DB_CONNECTION=sqlite
# DB_DATABASE=/chemin/absolu/vers/database/database.sqlite

# 9. Lancer les migrations
php artisan migrate

# 10. Créer les rôles
php artisan db:seed --class=RoleSeeder

# 11. Créer le compte admin
php artisan tinker
```

Dans tinker, exécuter :
```php
$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@admin.com',
    'password' => bcrypt('password'),
    'balance' => 1000
]);
$user->assignRole('admin');
exit
```

```bash
# 12. Builder le frontend
npm run build

# 13. Lancer le serveur
php artisan serve --host=0.0.0.0 --port=8000
```

**Accès :** `http://localhost:8000` ou `http://IP_VM:8000`

---

## Option 2 : Docker (MySQL + MongoDB)

### Prérequis Docker

```bash
# Installer Docker (Ubuntu/Debian)
sudo apt update
sudo apt install -y docker.io docker-compose git
sudo usermod -aG docker $USER
# Se déconnecter/reconnecter pour appliquer le groupe docker
```

### Lancement

```bash
# 1. Cloner le projet
git clone https://github.com/Valaraukar56/projet-ticket.git
cd projet-ticket

# 2. Lancer les containers
docker-compose up --build -d

# 3. Attendre ~30 secondes que tout démarre
docker-compose logs -f
```

**Accès :** `http://localhost:8080` ou `http://IP_VM:8080`

### Commandes Docker utiles

```bash
# Voir les logs
docker-compose logs -f

# Arrêter les containers
docker-compose down

# Redémarrer
docker-compose restart

# Reconstruire après modifications
docker-compose up --build -d
```

---

## Comptes par défaut

| Type | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@admin.com | password |

Les nouveaux utilisateurs peuvent s'inscrire via le formulaire d'inscription.

---

## Structure du projet

```
projet-ticket/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php      # Authentification
│   │   ├── AdminController.php     # Panel admin
│   │   └── LeaderboardController.php
│   ├── Models/
│   │   ├── User.php
│   │   └── GameLog.php             # Logs MongoDB
│   └── Services/
│       └── GameLogService.php
├── resources/js/
│   ├── App.vue                     # Composant principal
│   └── components/
│       ├── AuthForm.vue            # Connexion/Inscription
│       ├── InteractiveHost.vue     # Interface principale
│       ├── MetroTicket.vue         # Ticket style Astro
│       ├── CashTicket.vue          # Ticket style Cash
│       ├── MillionaireTicket.vue   # Ticket style Millionnaire
│       ├── ScratchTicket.vue       # Ticket générique
│       ├── OrganSlotMachine.vue    # Marché Noir
│       ├── AdminPanel.vue          # Panel admin
│       └── Leaderboard.vue         # Classement
├── database/
│   ├── migrations/
│   └── seeders/
│       └── RoleSeeder.php
├── docker-compose.yml
├── Dockerfile
└── docker-entrypoint.sh
```

---

## Développement

```bash
# Lancer le serveur de dev avec hot-reload
npm run dev

# Dans un autre terminal
php artisan serve

# Builder pour production
npm run build
```

---

## API Routes

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | /api/register | Inscription |
| POST | /api/login | Connexion |
| POST | /api/logout | Déconnexion |
| GET | /api/me | Utilisateur connecté |
| POST | /api/balance | Mettre à jour le solde |
| GET | /api/leaderboard | Top 50 joueurs |
| GET | /api/admin/users | Liste utilisateurs (admin) |
| GET | /api/admin/stats | Statistiques (admin) |
| DELETE | /api/admin/users/{id} | Supprimer utilisateur (admin) |

---

## Technologies

- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** Vue.js 3, Vite
- **Base de données:** SQLite (local) / MySQL (Docker)
- **Logs:** MongoDB (optionnel, Docker)
- **Auth:** Laravel Session + Spatie Permission

---

## Licence

Projet éducatif BTS SIO
