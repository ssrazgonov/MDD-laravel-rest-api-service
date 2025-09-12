<?php

namespace App\Modules\Card\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardStatusHistory extends Model
{
    protected $fillable = [
        'card_id',
        'status',
        'comment',
        'changed_by',
    ];

    protected $casts = [
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
