<?php

namespace Bgultekin\CashierFastspring\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Http\Request;
use Bgultekin\CashierFastspring\Tests\Fixtures\WebhookControllerTestStub;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Bgultekin\CashierFastspring\Events;

class WebhookControllerTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/../.env')) {
            $dotenv = new \Dotenv\Dotenv(__DIR__.'/../');
            $dotenv->load();
        }
    }

    public function testHmac()
    {
        $hmacSecret = 'dontlookiamsecret';
        Config::set('services.fastspring.hmac_secret', $hmacSecret);

        $webhookRequestPayload = [
            'events' => [
                [
                    'id' => 'id-1',
                    'live' => true,
                    'processed' => false,
                    'type' => 'account.created',
                    'created' => 1426560444800,
                    'data' => []
                ]
            ]
        ];
        
        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $request->headers->set(
            'X-FS-Signature',
            base64_encode(hash_hmac('sha256', $request->getContent(), $hmacSecret, true))
        );

        $controller = new WebhookControllerTestStub;
        $response = $controller->handleWebhook($request);

        $this->assertEquals($response->getStatusCode(), 202);
    }

    public function testHmacFailed()
    {
        Config::set('services.fastspring.hmac_secret', 'dontlookiamsecret');
        $this->expectException(\Exception::class);

        $webhookRequestPayload = [
            'events' => [
                [
                    'id' => 'id-1',
                    'live' => true,
                    'processed' => false,
                    'type' => 'account.created',
                    'created' => 1426560444800,
                    'data' => []
                ]
            ]
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub;
        $response = $controller->handleWebhook($request);
    }

    public function testMultipleWebhookEvents()
    {
        $webhookRequestPayload = [
            'events' => [
                [
                    'id' => 'id-1',
                    'live' => true,
                    'processed' => false,
                    'type' => 'account.created',
                    'created' => 1426560444800,
                    'data' => []
                ],
                [
                    'id' => 'id-2',
                    'live' => true,
                    'processed' => false,
                    'type' => 'subscription.activated',
                    'created' => 1426560444800,
                    'data' => []
                ]
            ]
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub;
        $response = $controller->handleWebhook($request);
        
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        $this->assertEquals($statusCode, 202);
        $this->assertEquals($content, "id-1\nid-2");
    }

    public function testMultipleWebhookEventsByFailingOne()
    {
        $webhookRequestPayload = [
            'events' => [
                [
                    'id' => 'id-1',
                    'live' => true,
                    'processed' => false,
                    'type' => 'account.created',
                    'created' => 1426560444800,
                    'data' => []
                ],
                [
                    'id' => 'id-2',
                    'live' => true,
                    'processed' => false,
                    'type' => 'subscription.notexistevent',
                    'created' => 1426560444800,
                    'data' => []
                ]
            ]
        ];

        // since the second event doesn't exist
        // there will be error and we only see first one handled
        // alson in the content of the response

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub;
        $response = $controller->handleWebhook($request);
        
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        $this->assertEquals($statusCode, 202);
        $this->assertEquals($content, "id-1");
    }

    public function testWebhooksEvents()
    {
        $webhookEvents = [
            'account.created',
            'fulfillment.failed',
            'mailingListEntry.removed',
            'mailingListEntry.updated',
            'order.approval.pending',
            'order.canceled',
            'order.payment.pending',
            'order.completed',
            'order.failed',
            'payoutEntry.created',
            'return.created',
            'subscription.activated',
            'subscription.canceled',
            'subscription.charge.completed',
            'subscription.charge.failed',
            'subscription.deactivated',
            'subscription.payment.overdue',
            'subscription.payment.reminder',
            'subscription.trial.reminder',
            'subscription.updated'
        ];

        foreach ($webhookEvents as $key => $webhookEvent) {
            $mockEvent = [
                'id' => 'id-'.$key,
                'live' => true,
                'processed' => false,
                'type' => $webhookEvent,
                'created' => 1426560444800,
                'data' => []
            ];

            // prepare category event class names like OrderAny
            $explodedType = explode('.', $mockEvent['type']);
            $category = array_shift($explodedType);
            $categoryEvent = 'Bgultekin\CashierFastspring\Events\\'.studly_case($category).'Any';

            // prepare category event class names like activity
            $activity = str_replace('.', ' ', $mockEvent['type']);
            $activityEvent = 'Bgultekin\CashierFastspring\Events\\'.studly_case($activity);

            $listenEvents = [
                Events\Any::class,
                $categoryEvent,
                $activityEvent
            ];
    
            $this->sendRequestAndListenEvents($mockEvent, $listenEvents);
        }
    }

    protected function sendRequestAndListenEvents($mockEvent, $listenEvents)
    {
        Event::fake();

        $webhookRequestPayload = [
            'events' => [
                $mockEvent
            ]
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub;
        $controller->handleWebhook($request);

        foreach ($listenEvents as $listenEvent) {
            // Assert
            Event::assertDispatched($listenEvent, function ($event) use ($mockEvent) {
                return (int) $event->id === (int) $mockEvent['id'];
            });
        }
    }
}
