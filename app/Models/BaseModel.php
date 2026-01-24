<?php

namespace App\Models;

use App\Traits\HasRegionalSettings;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasRegionalSettings;
}
