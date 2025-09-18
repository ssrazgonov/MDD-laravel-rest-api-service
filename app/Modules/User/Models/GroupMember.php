<?php

namespace App\Modules\User\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\User\Enums\GroupMemberType;
use App\Modules\User\Models\GroupMemberIdentity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'type',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => GroupMemberType::class,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'group_member_id');
    }

    public function activeCards(): HasMany
    {
        return $this->hasMany(Card::class, 'group_member_id')->where('status', CardStatus::ACTIVE);
    }

    public function hasActiveCard(): bool
    {
        return $this->activeCards()->exists();
    }

    public function scopePrimary($query)
    {
        return $query->where('type', 'primary');
    }

    public function scopeDependent($query)
    {
        return $query->where('type', 'dependent');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(GroupMemberIdentity::class);
    }

    public function primaryIdentity(?string $type = null): ?GroupMemberIdentity
    {
        $query = $this->identities();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->where('is_primary', true)->first();
    }

    public function addIdentity(string $type, string $value, bool $isPrimary = false): GroupMemberIdentity
    {
        $identity = GroupMemberIdentity::firstOrCreate([
            'type' => $type,
            'value' => $value,
        ], [
            'group_member_id' => $this->id,
            'is_primary' => $isPrimary,
        ]);

        if ($identity->group_member_id !== $this->id) {
            $identity->update(['group_member_id' => $this->id, 'is_primary' => $isPrimary]);
        }

        return $identity;
    }

    public function findIdentity(string $type, string $value): ?GroupMemberIdentity
    {
        return $this->identities()
            ->where('type', $type)
            ->where('value', $value)
            ->first();
    }

    public function attachUser(User|int|null $user): self
    {
        if (empty($user)) {
            return $this;
        }

        $userId = $user instanceof User ? $user->id : $user;

        User::where('id', $userId)->update([
            'group_member_id' => $this->id,
        ]);

        return $this;
    }
}
