<?php

namespace App\Models;

use App\Traits\HasRegionalSettings;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class BaseUser extends Authenticatable
{
    use HasRegionalSettings;
}
