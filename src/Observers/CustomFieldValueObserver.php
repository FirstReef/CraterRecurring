<?php

namespace FirstReef\CraterRecurring\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

use Crater\Models\Invoice;

use FirstReef\CraterRecurring\Jobs\CreateOrUpdateRecurringInvoice;

/**
 * See events.
 *
 * @href https://laravel.com/docs/5.5/eloquent#events
 * Available metthods: retrieved, creating, created, updating, updated,
 * saving, saved, deleting, deleted, restoring, restored
 */
class CustomFieldValueObserver
{
    /**
     * Listen to the created event.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function created(Model $model)
    {
        $this->createOrUpdateRecurring($model);
    }

    /**
     * Listen to the updated event.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function updated(Model $model)
    {
        $this->createOrUpdateRecurring($model);
    }

    /**
     * Check if custom field is for invoice and dispatch CreateOrUpdateRecurring job
     *
     * @param \Illuminate\Database\Eloquent\Model $model 
     */
    private function createOrUpdateRecurring(Model $model)
    {
        // Get fresh model
        $fresh = $model->fresh();

        // If not invoice custom field, exit
        if($fresh->custom_field_valuable_type !== Invoice::class) return; 

        // Create or update recurring details for invoice
        CreateOrUpdateRecurringInvoice::dispatchNow($fresh->customFieldValuable);
    }
}