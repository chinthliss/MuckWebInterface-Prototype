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
Default password for Vagrant is 'vagrant'. Remaining commands are from the opened shell:
```  
cd code  
php artisan migrate --seed  
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
composer install 
php artisan migrate
// php artisan cache:clear
// php artisan queue:restart
php artisan up
```
