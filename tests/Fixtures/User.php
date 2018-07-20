<?php

namespace Bgultekin\CashierFastspring\Tests\Fixtures;

use Bgultekin\CashierFastspring\Billable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class User extends Eloquent
{
    use Billable;
}
