<?php

namespace App\Base;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResource extends JsonResource
{
    public $with = [
        'status' => true,
        'error' => null,
    ];
}
