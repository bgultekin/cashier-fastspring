<?php

namespace Bgultekin\CashierFastspring\Listeners;

use Bgultekin\CashierFastspring\Events;
use Bgultekin\CashierFastspring\Subscription;

class Base
{
    /**
     * Get the billable entity instance by Fastspring ID.
     *
     * @param  string  $fastspringId
     * @return \Bgultekin\CashierFastspring\Billable
     */
    public function getUserByFastspringId($fastspringId)
    {
        $model = getenv('FASTSPRING_MODEL') ?: config('services.fastspring.model');
        return (new $model)->where('fastspring_id', $fastspringId)->first();
    }
}
