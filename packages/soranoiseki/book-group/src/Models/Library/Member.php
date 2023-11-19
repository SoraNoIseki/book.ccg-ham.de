<?php

namespace Soranoiseki\BookGroup\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $connection = 'openbiblio';

    protected $table = 'member';

    protected $primaryKey = 'mbrid';

    const CREATED_AT = 'create_dt';

    const UPDATED_AT = 'last_change_dt';
    
    protected $appends = [
        'full_name'
    ];

    protected $fillable = [
        'barcode_nmbr',
        'create_dt',
        'last_change_dt',
        'last_change_userid',
        'last_name',
        'first_name',
        'address',
        'home_phone',
        'work_phone',
        'email',
        'classification',
        'mbrshipend',
    ];

    public function getFullNameAttribute() {
        // TODO: english names?
        return trim($this->last_name) . trim($this->first_name);
    }

    
}
