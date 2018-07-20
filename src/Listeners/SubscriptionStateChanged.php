<?php

namespace Bgultekin\CashierFastspring\Listeners;

use Bgultekin\CashierFastspring\Events;
use Bgultekin\CashierFastspring\Subscription;

/**
 * This class is a listener for subscription state change events.
 * It is planned to listen following fastspring events:
 *  - subscription.canceled
 *  - subscription.payment.overdue
 * It updates related subscription event.
 *
 * IMPORTANT: This class handles expansion enabled webhooks.
 */
class SubscriptionStateChanged extends Base
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param \Bgultekin\CashierFastspring\Events\Base $event
     *
     * @return void
     */
    public function handle(Events\Base $event)
    {
        $data = $event->data;

        // create
        $subscription = Subscription::where('fastspring_id', $data['id'])->firstOrFail();

        // fill
        $subscription->user_id = $this->getUserByFastspringId($data['account']['id'])->id;
        $subscription->plan = $data['product']['product'];
        $subscription->state = $data['state'];
        $subscription->currency = $data['currency'];
        $subscription->quantity = $data['quantity'];

        // save
        $subscription->save();
    }
}
