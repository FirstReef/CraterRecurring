# CraterRecurring
A package for Crater Invoicing to enable recurring invoices.

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

That's it! You're ready to start creating recurring invoices!
