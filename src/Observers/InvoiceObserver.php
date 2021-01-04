<?php

namespace FirstReef\CraterRecurring\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

use FirstReef\CraterRecurring\Jobs\CreateOrUpdateRecurringInvoice;

/**
 * See events.
 *
 * @href https://laravel.com/docs/5.5/eloquent#events
 * Available metthods: retrieved, creating, created, updating, updated,
 * saving, saved, deleting, deleted, restoring, restored
 */
class InvoiceObserver
{
    /**
     * Listen to the creating event.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function created(Model $model)
    {
        $fresh = $model->fresh();
        CreateOrUpdateRecurringInvoice::dispatchNow($fresh);
    }

    /**
     * Listen to the creating event.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function updated(Model $model)
    {
        $fresh = $model->fresh();
        CreateOrUpdateRecurringInvoice::dispatchNow($fresh);
    }
}