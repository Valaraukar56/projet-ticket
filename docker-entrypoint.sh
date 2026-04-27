#!/bin/bash
set -e

echo "🚀 Démarrage de l'application Ticket Scratch..."

# Attendre que MySQL soit prêt
echo "⏳ Attente de MySQL..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --ssl-mode=DISABLED --silent 2>/dev/null; do
    sleep 2
done
echo "✅ MySQL est prêt!"

# Générer la clé si pas présente
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Exécuter les migrations
echo "📦 Exécution des migrations..."
php artisan migrate --force

# Créer les rôles et l'admin
echo "👤 Création des rôles et de l'admin..."
php artisan db:seed --class=RoleSeeder --force 2>/dev/null || true
php artisan db:seed --class=AdminSeeder --force 2>/dev/null || true

# Vider et reconstruire le cache
echo "🧹 Configuration du cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Application prête! Accès: http://localhost:8080"

# Lancer Apache
exec "$@"
