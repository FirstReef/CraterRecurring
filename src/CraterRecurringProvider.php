<?php

// namespace Crater\Providers;
namespace FirstReef\CraterRecurring;

use Illuminate\Support\ServiceProvider;

use Illuminate\Console\Scheduling\Schedule;

use FirstReef\CraterRecurring\Observers\Kernel as CRKernel;

class CraterRecurringProvider extends ServiceProvider
{

    protected $commands = [
        Commands\InstallCommand::class,
        Commands\CheckForRecurring::class,
    ];

    CONST RECURRING_FIELD_NAME  = 'fr_recurring_invoice';
    CONST FREQ_NEVER            = 'never';
    CONST FREQ_DAILY            = 'daily';
    CONST FREQ_WEEKLY           = 'weekly';
    CONST FREQ_MONTHLY          = 'monthly';
    CONST FREQ_YEARLY           = 'yearly';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register commands, such as installer recurring:install 
        $this->commands($this->commands);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish necessary assets
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadViewsFrom(__DIR__.'/views', 'craterrecurring');
        $this->publishes([
            __DIR__.'/views' => base_path('resources/views/firstreef/craterrecurring'),
        ]);

        // Set up our model observers. We need to observe the Invoices model to create/edit recurring settings.
        CRKernel::make()->observes();

        // Set up our schedule to check for recurring invoices
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('recurring:check')->daily();
        });
    }
    
}
