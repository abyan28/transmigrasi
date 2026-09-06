<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingEmailChange extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'pending_email_changes';

    protected $primaryKey = 'id_pending_email_change';

    protected $fillable = [
        'user_id',
        'new_email',
        'token_hash',
        'expires_at',
        'used_at',
        'cancelled_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now());
    }
}
