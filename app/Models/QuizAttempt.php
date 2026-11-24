<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $fillable = ['player_key', 'nickname', 'answers', 'question_started_ms'];

    protected $casts = [
        'answers' => 'array',
        'correct_count' => 'integer',
        'correct_time_ms' => 'integer',
        'question_started_ms' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuizBank::class, 'quiz_bank_id');
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    public function scopeRanked(Builder $query): void
    {
        $query->orderByDesc('correct_count')->orderBy('correct_time_ms')->orderBy('id');
    }
}
