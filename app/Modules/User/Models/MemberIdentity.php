<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberIdentity extends Model
{
    protected $fillable = [
        'group_member_id',
        'identity_type',
        'identity_value',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    public static function findByIdentity(string $type, string $value): ?GroupMember
    {
        $identity = self::where('identity_type', $type)
            ->where('identity_value', $value)
            ->first();

        return $identity?->groupMember;
    }

    public static function findIdentity(string $type, string $value): ?self
    {
        return self::where('identity_type', $type)
            ->where('identity_value', $value)
            ->first();
    }
}
