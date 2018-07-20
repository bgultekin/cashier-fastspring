<?php

namespace Bgultekin\CashierFastspring;

use Bgultekin\CashierFastspring\Fastspring\Fastspring;
use GuzzleHttp\Exception\ClientException;

class SubscriptionBuilder
{
    /**
     * The model that is subscribing.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $owner;

    /**
     * The name of the subscription.
     *
     * @var string
     */
    protected $name;

    /**
     * The name of the plan being subscribed to.
     *
     * @var string
     */
    protected $plan;

    /**
     * The quantity of the subscription.
     *
     * @var int
     */
    protected $quantity = 1;

    /**
     * The coupon code being applied to the customer.
     *
     * @var string|null
     */
    protected $coupon;

    /**
     * Create a new subscription builder instance.
     *
     * @param mixed  $owner
     * @param string $name
     * @param string $plan
     *
     * @return void
     */
    public function __construct($owner, $name, $plan)
    {
        $this->name = $name;
        $this->plan = $plan;
        $this->owner = $owner;
    }

    /**
     * Specify the quantity of the subscription.
     *
     * @param int $quantity
     *
     * @return $this
     */
    public function quantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * The coupon to apply to a new subscription.
     *
     * @param string $coupon
     *
     * @return $this
     */
    public function withCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * Create a new Fastspring session and return it as object.
     *
     * @return object
     */
    public function create()
    {
        $fastspringId = $this->getFastspringIdOfCustomer();

        return Fastspring::createSession($this->buildPayload($fastspringId));
    }

    /**
     * Get the fastspring id for the current user.
     *
     * @return int|string
     */
    protected function getFastspringIdOfCustomer()
    {
        if (!$this->owner->fastspring_id) {
            try {
                $customer = $this->owner->createAsFastspringCustomer();
            } catch (ClientException $e) {
                // we should get its id and save it
                $response = $e->getResponse();
                $content = json_decode($response->getBody()->getContents());

                // if email key exists in error node
                // then we assume this error is related to that
                // there is already an account with this email in fastspring-side
                // error message also returns account link but messages are easily
                // changable so we can't rely on that
                if (isset($content->error->email)) {
                    $response = Fastspring::getAccounts(['email' => $this->owner->email]);

                    if ($response->accounts) {
                        $account = $response->accounts[0];

                        // save it to eloquent model
                        $this->owner->fastspring_id = $account->id;
                        $this->owner->save();
                    }
                } else {
                    // if we are not sure about the exception
                    // then throw it again
                    // if returns it is yours, if doesn't it has never been yours
                    // (the previous line is a bad joke don't mind)
                    throw $e; // @codeCoverageIgnore
                }
            }
        }

        return $this->owner->fastspring_id;
    }

    /**
     * Build the payload for session creation.
     *
     * @return array
     */
    protected function buildPayload($fastspringId)
    {
        return array_filter([
            'account' => $fastspringId,
            'items'   => [
                [
                    'product'  => $this->plan,
                    'quantity' => $this->quantity,
                ],
            ],
            'tags' => [
                'name' => $this->name,
            ],
            'coupon' => $this->coupon,
        ]);
    }
}
