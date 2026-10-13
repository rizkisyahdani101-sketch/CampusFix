<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GuestIssueReporter extends Model
{
    protected $fillable = [
        'issue_id',
        'name',
        'email',
        'verification_token_hash',
        'email_verified_at',
    ];

    protected function casts(): array
    {
        return ['email_verified_at' => 'immutable_datetime'];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
