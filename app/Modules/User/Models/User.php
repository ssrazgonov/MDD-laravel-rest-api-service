<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public $fillable = [
        'id',
        'email',
        'group_member_id',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'group_member_id');
    }

    public function primaryGroupMember(): BelongsTo
    {
        return $this->groupMember()->primary();
    }
}
