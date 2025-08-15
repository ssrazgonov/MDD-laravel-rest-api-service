<?php

namespace App\Modules\Card\Models;

use App\Enums\CardStatus;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'user_id',
        'hashed_pan',
        'last_4_digits',
        'status',
        'comment'
    ];

        protected $casts = [
        'status' => CardStatus::class
    ];
}