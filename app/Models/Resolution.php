<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class Resolution extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'issue_id',
        'assignment_id',
        'resolved_by',
        'root_cause',
        'action_taken',
        'parts_used',
        'minutes_spent',
    ];

    protected function casts(): array
    {
        return [
            'minutes_spent' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (Resolution $resolution): void {
            throw new LogicException(sprintf(
                'Resolution record %s is append-only.',
                $resolution->getKey(),
            ));
        });

        static::deleting(static function (Resolution $resolution): void {
            throw new LogicException(sprintf(
                'Resolution record %s is append-only.',
                $resolution->getKey(),
            ));
        });
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
