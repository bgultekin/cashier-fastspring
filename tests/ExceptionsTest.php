<?php

namespace Bgultekin\CashierFastspring\Tests;

use Orchestra\Testbench\TestCase;
use Bgultekin\CashierFastspring\Exceptions\NotImplementedException;

class ExceptionsTest extends TestCase
{
    public function testNotImplementedExceptionCanBeConstructed()
    {
        $this->assertInstanceOf(NotImplementedException::class, new NotImplementedException());
    }
}
