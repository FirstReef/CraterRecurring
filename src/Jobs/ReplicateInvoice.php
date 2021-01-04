<?php

namespace FirstReef\CraterRecurring\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;

use Vinkla\Hashids\Facades\Hashids;

use Crater\Models\Invoice;
use Crater\Models\CompanySetting;

use FirstReef\CraterRecurring\CraterRecurringProvider as CRProvider;

use FirstReef\CraterRecurring\Mail\SendAdminRecurringInvoiceMail;

class ReplicateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoice;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($invoice)
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
        // Get todays date at 00:00:00 
        $now = Carbon::now()->startOfDay();

        // Get difference in days between invoice date and due date so we can replicate on new invoice.
        $due_days = $this->invoice->invoice_date->diffInDays($this->invoice->due_date);

        $invoice_prefix = CompanySetting::getSetting(
            'invoice_prefix',
            $this->invoice->company_id
        );
        
        // Replicate invoice, and update necessary attributes.
        $new_invoice = $this->invoice->replicate()->fill([
            'invoice_date'      => $now->toDateString(),
            'due_date'          => $now->addDays($due_days)->toDateString(),
            'invoice_number'    => $invoice_prefix . "-" . $this->getNextInvoiceNumber($invoice_prefix),
            'status'            => Invoice::STATUS_DRAFT, 
            'is_recurring'      => false, // Only the original invoice should be recurring.
            'parent_invoice_id' => $this->invoice->id,
            'unique_hash'       => null, // Temporarily set unique hash to NULL
        ]);

        // Save to get an ID and set the unique hash.
        $new_invoice->save();
        $new_invoice->update([
            'unique_hash'   => Hashids::connection(Invoice::class)->encode($new_invoice->id)
        ]);

        // Copy invoice items to new invoice
        foreach($this->invoice->items as $item) {
            $new_item = $item->replicate()->fill([
                'invoice_id' => $new_invoice->id
            ]);
            $new_item->save();
        }

        // Copy custom fields (excl recurring) to new invoice
        foreach($this->invoice->fields as $field) {
            $new_field = $field->replicate()->fill([
                'custom_field_valuable_id'  => $new_invoice->id
            ]);
            // If field is for recurring, set to never
            if($field->customField->name == CRProvider::RECURRING_FIELD_NAME){
                $new_field->string_answer = CRProvider::FREQ_NEVER;
            }
            $new_field->save();
        }

        // Send invoice using settings from first invoices email log
        $email_log = $this->invoice->emailLogs->sortByDesc('id')->first();
        $new_invoice->send([
            'from'      => $email_log->from,
            'to'        => $email_log->to,
            'body'      => $email_log->body,
            'subject'   => $email_log->subject
        ]);

        // Send notification email to company owner
        \Mail::to($new_invoice->company->user)->send(new SendAdminRecurringInvoiceMail($new_invoice));
    }

    /**
     * Replication of Invoice::getNextInvoiceNumber however I order by invoice number, not created at.
     */
    private function getNextInvoiceNumber($value)
    {
        $lastOrder = Invoice::where('invoice_number', 'LIKE', $value . '-%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (!$lastOrder) {
            $number = 0;
        } else {
            $number = explode("-", $lastOrder->invoice_number);
            $number = $number[1];
        }

        return sprintf('%06d', intval($number) + 1);
    }
}
