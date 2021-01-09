# CraterRecurring
A package for Crater Invoicing to enable recurring invoices.

## Prerequisites
In order for this package to run correctly, you will need to have the ability to set up Laravel Schedules (Cron). You may require SSH access in order to do this step, if you are using shared hosting.

## Installation
This package was developed for use with [Crater] (https://craterapp.com). 

After installing Crater and running the setup, install CraterRecurring with composer

```bash
composer require firstreef/craterrecurring
```

Crater is built on Laravel ^8.0, which should auto-discover the service provider. Alternatively you can add the following to __config/app.php__

```
'providers' => [
    ...
    FirstReef\CraterRecurring\CraterRecurringProvider::class
    ...
],
```

After installing, run the following from the command line:

```bash
php artisan recurring:install
```

Finally, to allow the package to automatically generate invoices, set up the laravel schedule/cron

### Using Laravel Forge, go to Server > Scheduling and add a new schedule to run every minute:
```
php7.4 /home/forge/{app_domain}/artisan schedule:run
```

### or setup via SSH
SSH into your server and run
```bash
crontab -e
```
In the file, paste the following (replacing path to your app)
```
* * * * * php /path/to/project/artisan schedule:run
```
Save this file, then restart crond
```bash
service crond restart
```

This will set a cron to run the laravel scheduler every minute.

### That's it! You're ready to start creating recurring invoices!
