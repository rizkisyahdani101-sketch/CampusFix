<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Issue extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'location_id',
        'location_detail',
        'status',
        'priority',
        'merged_into_issue_id',
        'safety_flag',
        'class_blocked',
    ];

    protected function casts(): array
    {
        return [
            'safety_flag' => 'boolean',
            'class_blocked' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_issue_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    // Pengguna yang melaporkan masalah.
    public function reporters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'issue_user')
            ->withPivot('relationship')
            ->withTimestamps()
            ->wherePivot('relationship', 'reporter');
    }

    public function guestReporter(): HasOne
    {
        return $this->hasOne(GuestIssueReporter::class);
    }

    // Pengguna yang terdampak masalah.
    public function affectedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'issue_user')
            ->withPivot('relationship')
            ->withTimestamps()
            ->wherePivot('relationship', 'affected');
    }

    // Pengguna yang mengikuti perkembangan laporan.
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'issue_user')
            ->withPivot('relationship')
            ->withTimestamps()
            ->wherePivot('relationship', 'follower');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class)->latest();
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
