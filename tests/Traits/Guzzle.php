<?php

namespace Bgultekin\CashierFastspring\Tests\Traits;

use Bgultekin\CashierFastspring\Fastspring\Fastspring;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

trait Guzzle
{
    /**
     * Set mock responses for guzzle.
     */
    protected function setMockResponsesAndHistory($responses, $history = null)
    {
        // prepare class for testing
        $mock = new MockHandler($responses);

        $handler = HandlerStack::create($mock);

        if ($history) {
            // Add the history middleware to the handler stack.
            $handler->push($history);
        }

        Fastspring::setClientOptions([
            'handler' => $handler,
        ]);
    }
}
