<?php
// app/Modules/Card/Models/CardStatusHistory.php

namespace App\Modules\Card\Models;

use App\Modules\Card\Models\Card;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\CardStatus;
use App\Modules\User\Models\User;

class CardStatusHistory extends Model
{
    protected $table = 'card_status_histories';

    protected $fillable = [
        'card_id',
        'user_id',
        'status',
        'comment',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'status' => CardStatus::class,
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}