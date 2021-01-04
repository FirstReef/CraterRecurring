<?php

namespace FirstReef\CraterRecurring\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use Vinkla\Hashids\Facades\Hashids;

use Carbon\Carbon;

use FirstReef\CraterRecurring\CraterRecurringProvider as CRProvider;
use FirstReef\CraterRecurring\RecurringPattern;

use FirstReef\CraterRecurring\Jobs\ReplicateInvoice;

use Crater\Models\Invoice;

class CheckForRecurring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for recurring invoices and check to see if today matches the recurring pattern';

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
        // Get all recurring invoices that are not drafts. If none, exit.
        $invoices = Invoice::where('status', '<>', Invoice::STATUS_DRAFT)->where('is_recurring', true)->get();
        \Log::info($invoices);
        if(!$invoices) return;

        // Get todays date at 00:00:00 
        $now = Carbon::now()->startOfDay();

        // Loop through recurring invoices and check the recurring pattern against today. If no pattern, exit loop.
        foreach($invoices as $invoice) {
            $pattern = RecurringPattern::where('invoice_id', $invoice->id)->first();
            if(!$pattern) continue;

            // TODO: is there a better way to check the pattern than this???
            if(
                // Schedule runs daily, always true
                $pattern->daily 
                // If day_of_week is set, we can assume it's weekly. 
                || (
                        !empty($pattern->day_of_week) 
                        && $pattern->day_of_week == $now->dayOfWeek
                    ) 
                // If only day_of_month is set, we can assume monthly
                || (
                        !empty($pattern->day_of_month) 
                        && empty($pattern->month_of_year) 
                        && $pattern->day_of_month == $now->format('d')
                    ) 
                // If today is the last day of month, and pattern day is greater than today
                // eg. Today is Feb 28 and the invoice/pattern date is Jan 31 we need to send the invoice.
                || (
                        !empty($pattern->day_of_month)
                        && $now->format('d') == $now->endOfMonth()->format('d')
                        && $pattern->day_of_month > $now->format('d')
                    )
                // If both day_of_month and month_of_year are set, we can assume yearly
                || (
                        !empty($pattern->day_of_month)
                        && !empty($pattern->month_of_year)
                        && $pattern->day_of_month == $now->format('d') 
                        && $pattern->month_of_year == $now->format('m')
                    ) 
            ) {
                // If today is before or same day as invoice date, skip. Recurring invoices should only happen in the future.
                $invoice_date = $invoice->invoice_date->startOfDay(); 
                if($now->lte($invoice_date)) return; 

                // Dispatch job to replicate invoice, and send it to the customer + admin(s)
                ReplicateInvoice::dispatchNow($invoice);
            }
        }

        return 0;
    }

    
}
