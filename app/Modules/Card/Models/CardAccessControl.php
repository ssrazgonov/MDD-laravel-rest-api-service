<?php

namespace App\Modules\Card\Models;

use App\Enums\AccessLevel;
use App\Modules\Card\Enums\CardAccessLevelEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\User\Models\User;
use \Illuminate\Support\Collection;

class CardAccessControl extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'card_id',
        'user_id',
        'access_level',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function canUserManageCard(User $user, Card $card): bool
    {
        return self::where('card_id', $card->id)
            ->where('user_id', $user->id)
            ->where('access_level', CardAccessLevelEnum::MANAGE)
            ->exists();
    }

    public static function canUserViewCard(User $user, Card $card): bool
    {
        return self::where('card_id', $card->id)
            ->where('user_id', $user->id)
            ->whereIn('access_level', [CardAccessLevelEnum::VIEW, CardAccessLevelEnum::MANAGE])
            ->exists();
    }

    public static function createForChildCard(Card $card, User $parent): static
    {
        return static::create([
            'card_id' => $card->id,
            'user_id' => $parent->id,
            'access_level' => CardAccessLevelEnum::VIEW,
            'created_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public static function getCardManagers(Card $card): Collection
    {
        return self::where('card_id', $card->id)
            ->where('access_level', 'manage')
            ->with('user')
            ->get()
            ->map(function (CardAccessControl $accessControl) {
                return $accessControl->user;
            });
    }
}