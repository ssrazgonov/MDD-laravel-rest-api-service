<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMemberIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_member_id',
        'type',
        'value',
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
        $identity = self::where('type', $type)
            ->where('value', $value)
            ->first();

        return $identity?->groupMember;
    }

    public static function findIdentity(string $type, string $value): ?self
    {
        return self::where('type', $type)
            ->where('value', $value)
            ->first();
    }
}
