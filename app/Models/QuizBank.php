<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizBank extends Model
{
    protected $fillable = ['questions', 'seconds_per_question'];

    protected $casts = [
        'questions' => 'array',
        'seconds_per_question' => 'integer',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(QuizMeeting::class, 'quiz_meeting_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
