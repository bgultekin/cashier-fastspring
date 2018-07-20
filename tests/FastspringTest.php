<?php

namespace Bgultekin\CashierFastspring\Tests;

use Bgultekin\CashierFastspring\Fastspring\ApiClient;
use Bgultekin\CashierFastspring\Fastspring\Fastspring;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;

/**
 * This class just tests if fastspring class works as php code and receive mocked responses.
 * It does not test compability of requests to fastspring API.
 */
class FastspringTest extends TestCase
{
    public $fastspring;

    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/.env')) {
            $dotenv = new \Dotenv\Dotenv(__DIR__);
            $dotenv->load();
        }
    }

    public function setUp()
    {
        // prepare class for testing
        $mock = new MockHandler(array_fill(
            0,
            20,
            new Response(200, [], json_encode(['hello' => 'world']))
        ));

        $handler = HandlerStack::create($mock);

        // create instance
        $fastspring = new ApiClient();

        $this->fastspring = $fastspring;
        $this->fastspring->setClientOptions([
            'handler' => $handler,
        ]);
    }

    public function testApiClientBuilderCanBeConstructed()
    {
        $this->assertInstanceOf(ApiClient::class, new ApiClient('username', 'password'));
    }

    public function testFastspringFacade()
    {
        // lets call it
        Fastspring::getAccounts();

        // check to instance type
        $this->assertInstanceOf(ApiClient::class, Fastspring::$instance);
    }

    public function testGlobalQuery()
    {
        $client = new ApiClient('username', 'password');
        $client->setGlobalQuery(['cats' => 'areBetter']);

        // actually we should check it in the requests
        // but for now it is ok
        $this->assertArrayHasKey('cats', $client->globalQuery);
        $this->assertEquals($client->globalQuery['cats'], 'areBetter');
    }

    public function testApiRequest()
    {
        $response = $this->fastspring->apiRequest(
            'POST',
            'something',
            ['query' => 'parameters'],
            ['form'  => 'parameters'],
            ['json'  => 'payload']
        );
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testCreateAccount()
    {
        $response = $this->fastspring->createAccount([]);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testUpdateAccount()
    {
        $response = $this->fastspring->updateAccount('id', []);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testGetSubscriptions()
    {
        $response = $this->fastspring->getSubscriptions([]);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testGetAccounts()
    {
        $response = $this->fastspring->getAccounts([]);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testGetOrders()
    {
        $response = $this->fastspring->getOrders([]);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testGetAccount()
    {
        $response = $this->fastspring->getAccount('id');
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testCreateSession()
    {
        $response = $this->fastspring->createSession([]);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testUpdateSubscriptions()
    {
        $response = $this->fastspring->updateSubscriptions([]);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testCancelSubscription()
    {
        $response = $this->fastspring->cancelSubscription('id');
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testUncancelSubscription()
    {
        $response = $this->fastspring->uncancelSubscription('id');
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testSwapSubscription()
    {
        $response = $this->fastspring->swapSubscription('id', 'new_plan', true);
        $this->assertObjectHasAttribute('hello', $response);
    }

    public function testGetAccountManagementURI()
    {
        $response = $this->fastspring->getAccountManagementURI('id');
        $this->assertObjectHasAttribute('hello', $response);
    }
}
