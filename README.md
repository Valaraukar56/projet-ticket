# Projet Ticket - Jeu de Grattage

Jeu de tickets à gratter style FDJ développé avec Laravel + Vue.js pour un projet BTS SIO.

## Fonctionnalités

- Tickets à gratter (Métro, Bus, Train, Loterie) avec différents thèmes visuels
- Système d'authentification avec rôles (admin/joueur)
- Solde persistant en base de données
- Classement des joueurs (top 50)
- Casino Tycoon débloquable à 5000$
- Chat en temps réel
- "Marché Noir des Organes" - machine à sous spéciale quand le solde atteint 0$
- Panel d'administration (stats, gestion utilisateurs, suivi tickets)
- Logging des actions avec MongoDB (optionnel)
- Compte admin protégé contre la suppression YOLO

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

# 9. Lancer les migrations et les seeders
php artisan migrate --seed

# 10. Builder le frontend
npm run build

# 11. Lancer le serveur
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
│   │   ├── AuthController.php          # Authentification + suppression compte
│   │   ├── AdminController.php         # Panel admin (stats, users, settings)
│   │   ├── TicketController.php        # API tickets (CRUD + suivi)
│   │   ├── PaymentController.php       # Achat tickets + gains + historique
│   │   ├── CasinoController.php        # Casino Tycoon
│   │   ├── ChatController.php          # Chat
│   │   └── LeaderboardController.php   # Classement
│   ├── Models/
│   │   ├── User.php
│   │   ├── Ticket.php
│   │   ├── Payment.php
│   │   ├── CasinoData.php
│   │   ├── ChatMessage.php
│   │   └── GameLog.php                 # Logs MongoDB
│   └── Services/
│       └── GameLogService.php
├── resources/js/
│   ├── App.vue                         # Composant principal
│   └── components/
│       ├── AuthForm.vue                # Connexion/Inscription
│       ├── InteractiveHost.vue         # Interface principale
│       ├── MetroTicket.vue             # Ticket style Astro
│       ├── CashTicket.vue              # Ticket style Cash
│       ├── MillionaireTicket.vue       # Ticket style Millionnaire
│       ├── RouletteTicket.vue          # Ticket style Roulette
│       ├── ScratchTicket.vue           # Ticket générique
│       ├── OrganSlotMachine.vue        # Marché Noir des Organes
│       ├── AdminPanel.vue              # Panel admin
│       ├── CasinoTycoon.vue            # Casino Tycoon
│       ├── GlobalChat.vue              # Chat
│       └── Leaderboard.vue             # Classement
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── RoleSeeder.php
│       └── AdminSeeder.php
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

### Authentification

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | /api/register | Inscription |
| POST | /api/login | Connexion |
| POST | /api/logout | Déconnexion |
| GET | /api/me | Utilisateur connecté |
| POST | /api/balance | Mettre à jour le solde |
| DELETE | /api/account | Supprimer son compte (YOLO chaos) |

### Jeu

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | /api/leaderboard | Top 50 joueurs |
| GET | /api/casino | Données Casino Tycoon |
| POST | /api/casino | Sauvegarder Casino Tycoon |
| POST | /api/casino/unlock | Débloquer le Casino |
| GET | /api/chat | Messages du chat |
| POST | /api/chat | Envoyer un message |

### Paiements

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | /api/payments/purchase | Acheter un ticket (débite le solde, trace le paiement) |
| POST | /api/payments/win | Encaisser un gain (crédite le solde, trace le paiement) |
| GET | /api/payments | Historique des paiements de l'utilisateur connecté |

### Tickets (API Avancée)

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | /api/tickets | Créer un ticket à l'achat |
| PATCH | /api/tickets/{id}/complete | Clore un ticket avec son résultat |
| GET | /api/open-tickets | Tickets en cours (purchased/scratching) |
| GET | /api/closed-tickets | Tickets terminés (completed) |
| GET | /api/users/{email}/tickets | Tickets d'un utilisateur par email |

### Administration

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | /api/admin/stats | Statistiques globales |
| GET | /api/admin/users | Liste des utilisateurs |
| PUT | /api/admin/users/{id}/balance | Modifier le solde d'un utilisateur |
| DELETE | /api/admin/users/{id} | Supprimer un utilisateur |
| GET | /api/admin/tickets | Paramètres des tickets/slots |
| PUT | /api/admin/tickets | Modifier les paramètres |

---

## Technologies

- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** Vue.js 3, Vite
- **Base de données:** SQLite (local) / MySQL (Docker)
- **Permissions:** Spatie Laravel Permission
- **Logs:** MongoDB (optionnel, Docker)

---

## Licence

Projet éducatif BTS SIO
