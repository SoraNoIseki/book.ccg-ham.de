<?php

namespace Soranoiseki\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $connection = 'openbiblio';

    protected $table = 'member';

    
}
