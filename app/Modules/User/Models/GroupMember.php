<?php

namespace App\Modules\User\Models;

use App\Modules\Card\Enums\CardStatus;
use App\Modules\Card\Models\Card;
use App\Modules\User\Models\MemberIdentity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'member_type',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'member_id');
    }

    public function activeCards(): HasMany
    {
        return $this->hasMany(Card::class, 'member_id')->where('status', CardStatus::ACTIVE);
    }

    public function hasActiveCard(): bool
    {
        return $this->activeCards()->exists();
    }

    public function scopePrimary($query)
    {
        return $query->where('member_type', 'primary');
    }

    public function scopeDependent($query)
    {
        return $query->where('member_type', 'dependent');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(MemberIdentity::class);
    }

    public function primaryIdentity(string $type = null): ?MemberIdentity
    {
        $query = $this->identities();

        if ($type) {
            $query->where('identity_type', $type);
        }

        return $query->where('is_primary', true)->first();
    }

    public function addIdentity(string $type, string $value, bool $isPrimary = false): MemberIdentity
    {
        $identity = MemberIdentity::firstOrCreate([
            'identity_type' => $type,
            'identity_value' => $value,
        ], [
            'group_member_id' => $this->id,
            'is_primary' => $isPrimary,
        ]);

        // If it was just created, it's already associated
        // If it existed, update the group_member_id
        if ($identity->group_member_id !== $this->id) {
            $identity->update(['group_member_id' => $this->id, 'is_primary' => $isPrimary]);
        }

        return $identity;
    }

    public function findIdentity(string $type, string $value): ?MemberIdentity
    {
        return $this->identities()
            ->where('identity_type', $type)
            ->where('identity_value', $value)
            ->first();
    }
}
