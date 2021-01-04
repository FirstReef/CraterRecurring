<?php

namespace FirstReef\CraterRecurring\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Database\Eloquent\Model;

use FirstReef\CraterRecurring\CraterRecurringProvider as CRProvider;

use Crater\Models\Invoice;
use Crater\Models\CustomField;

use Carbon\Carbon;

use FirstReef\CraterRecurring\Models\RecurringPattern;

class CreateOrUpdateRecurringInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoice;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get all matching custom fields in company
        $company_id = $this->invoice->company_id;
        $fields = CustomField::where('company_id', $company_id)
            ->where('name', CRProvider::RECURRING_FIELD_NAME)
            ->get();
        
        // If matching custom field not found, exit 
        if(!$fields) return; 

        // Check to see if model has a value for matching custom fields. Get first answer.
        $frequency = $this->invoice->fields
            ->whereIn('custom_field_id', $fields->pluck('id')->toArray())
            ->first();

        if(!$frequency) return;

        switch ($frequency->defaultAnswer) {
            case CRProvider::FREQ_NEVER:
                // Delete recurring pattern and set recurring to false
                $this->removeFrequency(); 
                $this->setRecurringInvoice(false);
                break;
            case CRProvider::FREQ_DAILY:
                $this->setDailyFrequency();
                $this->setRecurringInvoice(true);
                break;
            case CRProvider::FREQ_WEEKLY:
                $this->setWeeklyFrequency();
                $this->setRecurringInvoice(true);
                break;
            case CRProvider::FREQ_MONTHLY:
                $this->setMonthlyFrequency();
                $this->setRecurringInvoice(true);
                break;
            case CRProvider::FREQ_YEARLY:
                $this->setYearlyFrequency();
                $this->setRecurringInvoice(true);
                break;
        }

    }

    private function removeFrequency()
    {
        $pattern = RecurringPattern::where('invoice_id', $this->invoice->id)->first();
        if(!$pattern) return;
        $pattern->delete();
    }

    private function setDailyFrequency()
    {
        // Daily invoice don't need a pattern. Simply set the daily boolean to true.
        $pattern = RecurringPattern::updateOrCreate(
            [
                'invoice_id' => $this->invoice->id
            ],
            [
                'daily' => true,
                'day_of_week'   => null,
                'day_of_month'  => null,
                'month_of_year' => null
            ]
        );
    }

    private function setWeeklyFrequency()
    {
        $pattern = RecurringPattern::updateOrCreate(
            [
                'invoice_id' => $this->invoice->id
            ],
            [
                'daily' => false,
                'day_of_week'   => $this->invoice->invoice_date->dayOfWeek,
                'day_of_month'  => null,
                'month_of_year' => null
            ]
        );
    }

    private function setMonthlyFrequency()
    {
        $pattern = RecurringPattern::updateOrCreate(
            [
                'invoice_id' => $this->invoice->id
            ],
            [
                'daily' => false,
                'day_of_week'   => null,
                'day_of_month'  => $this->invoice->invoice_date->format('d'),
                'month_of_year' => null
            ]
        );
    }

    private function setYearlyFrequency()
    {
        $pattern = RecurringPattern::updateOrCreate(
            [
                'invoice_id' => $this->invoice->id
            ],
            [
                'daily' => false,
                'day_of_week'   => null,
                'day_of_month'  => $this->invoice->invoice_date->format('d'),
                'month_of_year' => $this->invoice->invoice_date->format('m')
            ]
        );
    }

    private function setRecurringInvoice($bool = true)
    {
        $this->invoice->is_recurring = $bool;
        $this->invoice->saveQuietly(); // IMPORTANT: save quietly to avoid infinitely looping the updated observer.
    }
}
