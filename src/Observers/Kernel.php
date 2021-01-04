<?php

namespace FirstReef\CraterRecurring\Observers;

class Kernel
{
    /**
     * Make this class.
     *
     * @return \App\Observers\Kernel
     */
    public static function make()
    {
        return new self();
    }

    /**
     * Register observers.
     */
    public function observes()
    {
        // We need to observe both invoices and custom field values. Values are created/edited after invoices, so we can't always see
        // these edits at the invoice level however we also need to make changes to the custom field when the invoice date
        // is edited.
        $observers = [
            \FirstReef\CraterRecurring\Observers\InvoiceObserver::class => [
                \Crater\Models\Invoice::class
            ],
            \FirstReef\CraterRecurring\Observers\CustomFieldValueObserver::class => [
                \Crater\Models\CustomFieldValue::class
            ],
        ];

        foreach ($observers as $observer => $models) {
            foreach ($models as $model) {
                if (class_exists($model) && class_exists($observer)) {
                    $model::observe($observer);
                }
            }
        }
    }
}