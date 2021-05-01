Need to flesh out instructions here

## Deployment
Make sure Composer is installed  
Download files to a folder / Clone from git  
Rename .env.example to .env  
Fill out settings in .env  
Run commands:  
```
composer install  
npm install
php artisan key:generate
```

### Production
```
php artisan config:cache
php artisan route:cache
npm run production
php artisan migrate
```

### Development
```  
vagrant up   
vagrant ssh
```
Then from within the opened shell:
```  
cd code  
php artisan migrate --seed
php config:clear
php route:clear
php cache:clear
```
Then use one of the following scripts:  
Single compile - `npm run dev`  
Watch files - `npm run watch`  
Live/HMR replacement - `npm run hot`  

## Updating

### Production
```
php artisan down
git pull origin master
php composer.phar install
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan cache:clear
// php artisan queue:restart
npm install
npm run production
php artisan up
```

## Maintenance cheat sheet
* Entire web server can be disabled by running `php artisan down` from the root dir. Opposite is `php artisan up`.
* Muck equivalent is `mwi down` and `mwi up` which will refuse connections between it and the web server.
* Environment variables (Such as credentials) stored in `/.env`
* Logs are located in `/storage/logs/`
* Terms-of-service located in `/public/terms-of-service.txt`

