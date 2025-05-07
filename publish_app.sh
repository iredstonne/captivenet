cd ./app
sudo rm -rf "/var/www/app"
sudo cp -r . "/var/www/app"
sudo COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction >/dev/null 2>&1
sudo COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-interaction  >/dev/null 2>&1
npm run build
