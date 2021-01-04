<?php
namespace FirstReef\CraterRecurring\Mail;

use Crater\Models\EmailLog;
use Crater\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAdminRecurringInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data = [];
    public $invoice;
    public $new_invoice;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($new_invoice)
    {
        $this->new_invoice = $new_invoice;
        $this->invoice = Invoice::find($new_invoice->parent_invoice_id);
        $this->data['company'] = $this->invoice->company;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("New recurring invoice has been sent")
                    ->markdown('craterrecurring::emails.send.newrecurring', [
                        'invoice' => $this->invoice, 
                        'new_invoice' => $this->new_invoice
                    ]);
    }
}
