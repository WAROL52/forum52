#!/bin/sh

APP_PATH=""

git pull

rm -rf "$APP_PATH/src"
rm -rf "$APP_PATH/migrations"
rm -rf "$APP_PATH/templates"

# Supprimer tout sauf config/jwt et son contenu
find "$APP_PATH/config" -mindepth 1 -not -path "$APP_PATH/config/jwt*" -exec rm -rf {} +

# Copier les nouveaux fichiers/dossiers
cp -R composer.* src migrations config templates "$APP_PATH/"

cd "$APP_PATH" && composer install
php "$APP_PATH/bin/console" doctrine:schema:update --force
php "$APP_PATH/bin/console" cache:clear --env=prod
