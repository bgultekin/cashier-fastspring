<?php

namespace Bgultekin\CashierFastspring;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'subscription_period_start_date',
        'subscription_period_end_date',
    ];

    /**
     * Get the user that owns the invoice.
     */
    public function user()
    {
        return $this->owner();
    }

    /**
     * Get the model related to the invoice.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        $model = getenv('FASTSPRING_MODEL') ?: config('services.fastspring.model', 'App\\User');

        $model = new $model();

        return $this->belongsTo(get_class($model), $model->getForeignKey());
    }
}
