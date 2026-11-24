<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizMeeting extends Model
{
    protected $fillable = ['title'];

    public function scopeActive(Builder $query): void
    {
        $query->where('active_slot', 1);
    }

    public function banks(): HasMany
    {
        return $this->hasMany(QuizBank::class);
    }

    public function currentBank(): HasOne
    {
        return $this->hasOne(QuizBank::class)->latestOfMany();
    }
}
