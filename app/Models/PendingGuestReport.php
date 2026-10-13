<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PendingGuestReport extends Model
{
    protected $fillable = [
        'name',
        'email',
        'title',
        'description',
        'category_id',
        'location_id',
        'location_detail',
        'safety_flag',
        'class_blocked',
        'photo_path',
        'photo_original_name',
        'photo_mime_type',
        'photo_file_size',
        'verification_token_hash',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'safety_flag' => 'boolean',
            'class_blocked' => 'boolean',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
