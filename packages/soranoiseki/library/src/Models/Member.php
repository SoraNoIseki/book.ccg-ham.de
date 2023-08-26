<?php

namespace Soranoiseki\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $connection = 'openbiblio';

    protected $table = 'member';
    
    protected $appends = [
        'full_name'
    ];

    public function getFullNameAttribute() {
        // TODO: english names?
        return trim($this->last_name) . trim($this->first_name);
    }

    
}
