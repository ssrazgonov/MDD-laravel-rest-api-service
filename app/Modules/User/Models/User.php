<?php

namespace App\Modules\User\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    public $fillable = [
        'id',
        'email',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function primaryGroupMember(): ?GroupMember
    {
        return $this->groupMembers()->primary()->active()->first();
    }

    public function groups()
    {
        return $this->hasManyThrough(Group::class, GroupMember::class, 'user_id', 'id', 'id', 'group_id');
    }

    public function card(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, GroupMember::class, 'user_id', 'member_id', 'id', 'id');
    }

    public function activeCards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, GroupMember::class, 'user_id', 'member_id', 'id', 'id')->where('status', CardStatus::ACTIVE);
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
