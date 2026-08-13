<?php

namespace Soranoiseki\BookGroup\Models\Finance;

use MongoDB\Laravel\Eloquent\Model;

class FinanceInfo extends Model
{
    protected $connection = 'mongodb-finance';

    protected $table = 'finance_info';
}
