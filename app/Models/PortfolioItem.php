<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PortfolioItem extends Model
{
    protected $appends = [
        'image_src',
    ];

    protected $fillable = [
        'sort_order',
        'is_active',
        'badge_id',
        'badge_en',
        'title_id',
        'title_en',
        'subtitle_id',
        'subtitle_en',
        'image_url',
        'project_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageSrcAttribute(): ?string
    {
        $path = $this->getRawOriginal('image_url');

        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return route('uploads.show', ['path' => $path]);
    }
}
