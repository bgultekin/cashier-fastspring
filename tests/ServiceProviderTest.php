<?php

namespace Bgultekin\CashierFastspring\Tests;

use Bgultekin\CashierFastspring\CashierServiceProvider;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tests.
     */
    public function testServiceProviderCanBeConstructed()
    {
        $this->assertInstanceOf(CashierServiceProvider::class, new CashierServiceProvider(app()));
    }

    public function testRegisterMethod()
    {
        $serviceProvider = new CashierServiceProvider(app());
        $this->assertNull($serviceProvider->register());
    }

    // TODO: should test if it moves migration files or not
    public function testBootMethod()
    {
        $serviceProvider = new CashierServiceProvider(app());
        $this->assertNull($serviceProvider->boot());
    }
}
