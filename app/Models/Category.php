<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeOrderForReporting(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN LOWER(TRIM(name)) = ? THEN 1 ELSE 0 END', ['fasilitas lainnya'])
            ->orderBy('name');
    }
}
