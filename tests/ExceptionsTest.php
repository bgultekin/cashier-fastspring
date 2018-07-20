<?php

namespace Bgultekin\CashierFastspring\Tests;

use Bgultekin\CashierFastspring\Exceptions\NotImplementedException;
use Orchestra\Testbench\TestCase;

class ExceptionsTest extends TestCase
{
    public function testNotImplementedExceptionCanBeConstructed()
    {
        $this->assertInstanceOf(NotImplementedException::class, new NotImplementedException());
    }
}
