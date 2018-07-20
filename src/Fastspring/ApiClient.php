<?php

namespace Bgultekin\CashierFastspring\Fastspring;

use GuzzleHttp\Client;

/**
* ApiClient is a simple class for sending requests to FastSpring API.
*
* This class aims to handle some APIs of Fastspring if you think to develop
* and add some other features please consider not doing it with one class.
*
* Note: This class does not cover whole FastSpring API, it covers just a couple of things
* in FastSpring API like accounts and sessions.
*
* Example usage:
* ```php
* $fastspring = new ApiClient();
* $accounts = $fastspring->getAccounts();
* ```
*
* @package  CashierFastspring\Fastspring
* @author   Bilal Gultekin <bilal@gultekin.me>
* @version  0.1
* @see      https://docs.fastspring.com/integrating-with-fastspring/fastspring-api
*/
class ApiClient
{

    /**
     * The Fastspring API Username.
     *
     * @var string
     */
    public $username;

    /**
     * The Fastspring API password.
     *
     * @var string
     */
    public $password;

    /**
     * The Fastspring API Base Url.
     *
     * @var string
     */
    public $apiBase = "https://api.fastspring.com";

    /**
     * Global queries to apply every requests
     *
     * @var array
     */
    public $globalQuery = [];

    /**
     * Guzzle client options.
     * Can be used to test class or process.
     *
     * @var array
     */
    public $clientOptions = [];

    /**
     * Create a new Fastspring API interface instance.
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function __construct($username = null, $password = null)
    {
        $this->username = $username
            ? $username
            : (getenv('FASTSPRING_USERNAME') ?: config("services.fastspring.username"));
        $this->password = $password
            ? $password
            : (getenv('FASTSPRING_PASSWORD') ?: config("services.fastspring.password"));
    }

    /**
     * Send a request to Fastspring API with given parameters.
     *
     * @param string $method Method of HTTP request like PUT, GET, POST etc.
     * @param string $path Path of API
     * @param string $query Query parameters
     * @param string $formParameters Form parameters
     * @param string $jsonPayload Json payload
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function apiRequest($method, $path, $query = [], $formParameters = [], $jsonPayload = [])
    {
        // prepare guzzle options
        $clientOptions = $this->clientOptions ?: ['base_uri' => $this->apiBase];

        // create guzzle instance
        $client = new Client($clientOptions);

        // delete first slash character
        $path = ltrim($path, '/');

        // prepare options
        $options = [
            'auth' => [$this->username, $this->password],
            'query' => $this->globalQuery
        ];

        // set parameters
        $options['query'] = array_merge($options['query'], $query);

        if ($formParameters) {
            $options['form_params'] = $formParameters;
        }
        
        if ($jsonPayload) {
            $options['json'] = $jsonPayload;
        }

        // send request and get response
        $response = $client->request($method, $path, $options);

        // convert it to object
        return $this->handleResponse($response);
    }

    /**
     * Set guzzle client options.
     *
     * @param array $options Guzzle client options
     * @see http://docs.guzzlephp.org/en/latest/quickstart.html Quickstart
     * @see http://docs.guzzlephp.org/en/latest/testing.html Testing
     * @return void
     */
    public function setClientOptions($options)
    {
        return $this->clientOptions = $options;
    }

    /**
     * Set global query items.
     *
     * @param array $query Queries like ['mode' => 'test']
     * @return void
     */
    public function setGlobalQuery($query)
    {
        return $this->globalQuery = $query;
    }

    /**
     * Handle JSON response and convert it to array.
     *
     * @param \GuzzleHttp\Psr7\Response $response Guzzle response
     *
     * @return object
     */
    protected function handleResponse($response)
    {
        $message = $response->getBody()->getContents();
        
        // json decode
        // we assume fastspring sends always json
        return json_decode($message);
    }

    # API methods

    /**
     * Create account.
     *
     * @param array $account Account details
     * @see https://docs.fastspring.com/integrating-with-fastspring/fastspring-api/accounts  Account details
     *
     * @return object Response of fastspring
     */
    public function createAccount($account)
    {
        return $this->apiRequest('POST', 'accounts', [], [], $account);
    }

    /**
     * Update account.
     *
     * @param string $fastspringId Fastspring ID of related account
     * @param array $account Account details
     * @see https://docs.fastspring.com/integrating-with-fastspring/fastspring-api/accounts  Account details
     *
     * @return object Response of fastspring
     */
    public function updateAccount($fastspringId, $account)
    {
        return $this->apiRequest('POST', implode('/', ['accounts', $fastspringId]), [], [], $account);
    }
    
    /**
     * Get account list.
     *
     * @param array $parameters Query parameters
     *
     * @return object Response of fastspring
     */
    public function getAccounts($parameters = [])
    {
        return $this->apiRequest('GET', 'accounts', $parameters, [], []);
    }

    /**
     * Get the account with the given id.
     *
     * @param String|Integer $accountId ID of the account
     * @param array $parameters Query Parameters
     *
     * @return object Response of fastspring
     */
    public function getAccount($accountId, $parameters = [])
    {
        return $this->apiRequest('GET', implode('/', ['accounts', $accountId]), $parameters, [], []);
    }

    /**
     * Create session.
     *
     * @param array $session Sessions details
     * @see https://docs.fastspring.com/integrating-with-fastspring/fastspring-api/sessions  Session details
     *
     * @return object Response of fastspring
     */
    public function createSession($session)
    {
        return $this->apiRequest('POST', 'sessions', [], [], $session);
    }
    
    /**
     * Get orders.
     *
     * @param array $parameters Query parameters
     *
     * @return object Response of fastspring
     */
    public function getOrders($parameters = [])
    {
        return $this->apiRequest('GET', 'accounts', $parameters, [], []);
    }

    /**
     * Get subscriptions.
     *
     * @param array $subscriptionIds Fastspring ids of subscriptions
     * @see https://docs.fastspring.com/integrating-with-fastspring/fastspring-api/subscriptions#id-/subscriptions-Getoneormoresubscriptioninstances
     *
     * @return object Response of fastspring
     */
    public function getSubscriptions($subscriptionIds)
    {
        return $this->apiRequest('GET', implode(
            '/',
            ['subscriptions', implode(',', $subscriptionIds)]
        ), [], [], []);
    }

    /**
     * Get subscription, returns one instance.
     *
     * @param array $subscriptionId Fastspring id of subscriptions
     * @see https://docs.fastspring.com/integrating-with-fastspring/fastspring-api/subscriptions#id-/subscriptions-Getoneormoresubscriptioninstances
     *
     * @return object Response of fastspring
     */
    public function getSubscriptionsEntries($subscriptionIds)
    {
        return $this->apiRequest('GET', implode(
            '/',
            ['subscriptions', implode(',', $subscriptionIds), 'entries']
        ), [], [], []);
    }

    /**
     * Update subscriptions.
     *
     * @param array $subscriptions Data of all subscriptions wanted to be updated (should include subscription => $id)
     * @see https://docs.fastspring.com/integrating-with-fastspring/fastspring-api/subscriptions#id-/subscriptions-Updateexistingsubscriptioninstances
     *
     * @return object Response of fastspring
     */
    public function updateSubscriptions($subscriptions)
    {
        return $this->apiRequest('POST', 'subscriptions', [], [], [
            'subscriptions' => $subscriptions
        ]);
    }

    /**
     * Cancel subscription.
     *
     * @param String|Integer $subscriptionId ID of the subscription
     * @param array $parameters Query Parameters for example to delete immediately pass ['billingPeriod' => 0]
     *
     * @return object Response of fastspring
     */
    public function cancelSubscription($subscriptionId, $parameters = [])
    {
        return $this->apiRequest('DELETE', implode('/', ['subscriptions', $subscriptionId]), $parameters, [], []);
    }

    /**
     * Uncancel subscription.
     *
     * @param String|Integer $subscriptionId ID of the subscription
     *
     * @return object Response of fastspring
     */
    public function uncancelSubscription($subscriptionId)
    {
        return $this->updateSubscriptions([
            [
                'subscription' => $subscriptionId,
                'deactivation' => null
            ]
        ]);
    }

    /**
     * Get authenticated url of fastspring account management panel.
     *
     * @param String|Integer $accountId ID of the account
     *
     * @return object Response of fastspring
     */
    public function getAccountManagementURI($accountId)
    {
        return $this->apiRequest('GET', implode('/', ['accounts', $accountId, 'authenticate']), [], [], []);
    }

    /**
     * Swap subscription to another plan.
     *
     * @param String|Integer $subscriptionId ID of the subscription
     * @param String $newPlan Name of the new plan
     * @param bool $prorate Prorate parameter
     * @param Integer $quantity Quantity of the product
     * @param array $coupons Coupons wanted to be applied
     *
     * @return object Response of fastspring
     */
    public function swapSubscription($subscriptionId, $newPlan, $prorate, $quantity = 1, $coupons = [])
    {
        return $this->updateSubscriptions([
            [
                'subscription' => $subscriptionId,
                'product' => $newPlan,
                'quantity' => $quantity,
                'coupons' => $coupons,
                'prorate' => $prorate
            ]
        ]);
    }
}
