<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class StatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'issue_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (StatusHistory $history): void {
            throw new LogicException(sprintf(
                'Status history entry %s is append-only.',
                $history->getKey(),
            ));
        });

        static::deleting(static function (StatusHistory $history): void {
            throw new LogicException(sprintf(
                'Status history entry %s is append-only.',
                $history->getKey(),
            ));
        });
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
