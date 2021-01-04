<?php

namespace FirstReef\CraterRecurring\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use FirstReef\CraterRecurring\CraterRecurringProvider as CRProvider;

use Crater\Models\CustomField;
use Crater\Models\Company;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Final setup for recurring invoices package.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking migrations are up to date...');
        Artisan::call('migrate --force');
        $this->info(Artisan::output());

        $this->info('Checking custom fields...');

        foreach(Company::all() as $company) {
            if(!CustomField::where('name', CRProvider::RECURRING_FIELD_NAME)->where('company_id', $company->id)->first()){
                $this->info('Creating recurring invoice field for ' . $company->name);

                 CustomField::create([
                    'name'          => CRProvider::RECURRING_FIELD_NAME,
                    'slug'          => clean_slug('Invoice', 'Recurring Invoice'),
                    'label'         => 'Recurring Invoice',
                    'model_type'    => 'Invoice',
                    'type'          => 'Dropdown',
                    'options'       => [
                        CRProvider::FREQ_NEVER,
                        CRProvider::FREQ_DAILY,
                        CRProvider::FREQ_WEEKLY,
                        CRProvider::FREQ_MONTHLY,
                        CRProvider::FREQ_YEARLY
                    ],
                    'string_answer' => 'never',
                    'is_required'   => true,
                    'order'         => 1,
                    'company_id'    => $company->id
                 ]);
            }
        }

        $this->info('Ready to go! You can now select a recurring interval when creating/editing invoices!');

        return 0;
    }
}
