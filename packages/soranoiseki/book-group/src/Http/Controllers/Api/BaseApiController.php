<?php

namespace Soranoiseki\BookGroup\Http\Controllers\Api;

use Soranoiseki\Core\Controllers\Controller;
use Illuminate\Http\Request;
use Soranoiseki\Core\Traits\ApiResponse;

class BaseApiController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
       
    }

    public function getPlansByYear(Request $request, $year)
    {
        // Logic to get plans by year
        // This is a placeholder for the actual implementation
        return $this->respondSuccess([
            'year' => $year,
            'plans' => [] // Replace with actual plans data
        ]);
    }

}
