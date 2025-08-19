<?php

namespace App\Modules\User\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    public function card(): HasMany {
        return $this->hasMany(Card::class);
    }

    public function activeCards(): HasMany 
    {
        return $this->hasMany(Card::class)->where('status', CardStatus::ACTIVE);
    }

    public function activeCard(): ?Card 
    {
        return $this->activeCards()->first();
    }

    public function hasActiveCard(): bool
    {
        return $this->activeCards()->exists();
    }
}