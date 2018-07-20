<?php

namespace Bgultekin\CashierFastspring;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPeriod extends Model
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
        'start_date', 'end_date',
        'created_at', 'updated_at',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function subscription()
    {
        return $this->belongsTo('Bgultekin\CashierFastspring\Subscription');
    }
}
