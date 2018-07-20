<?php

namespace Bgultekin\CashierFastspring\Tests\Traits;

use Bgultekin\CashierFastspring\Tests\Fixtures\User;

trait Model
{
    public function createUser($parameters = [])
    {
        return User::create(array_merge([
            'email' => 'bilal@gultekin.me',
            'name'  => 'Bilal Gultekin',
        ], $parameters));
    }

    public function createSubscription($user, $parameters = [])
    {
        return $user->subscriptions()->create(array_merge([
            'name'            => 'main',
            'fastspring_id'   => 'fastspring_id',
            'plan'            => 'starter-plan',
            'state'           => 'active',
            'quantity'        => 1,
            'currency'        => 'USD',
            'interval_unit'   => 'month',
            'interval_length' => 1,
        ], $parameters));
    }

    public function createSubscriptionPeriod($subscription, $parameters = [])
    {
        return $subscription->periods()->create(array_merge([
            'type'       => 'local',
            'start_date' => '2010-01-01',
            'end_date'   => '2010-02-01',
        ], $parameters));
    }
}
