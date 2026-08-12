<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KoKiProgress extends Model
{
    protected $table = 'ko_ki_progresses';

    protected $fillable = [
        'user_id',
        'student_name',
        'grade',
        'semester',
        'unit',
        'meeting',
        'last_phase',
        'current_level',
        'approved_levels',
        'initial_answer',
        'completed_levels',
        'attempt_count',
        'level_attempts',
        'last_program',
        'level_programs',
        'predictions',
        'level_states',
        'lab_state',
        'ipo_state',
        'exit_ticket',
        'exit_ticket_answers',
        'reflection',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'semester' => 'integer',
            'unit' => 'integer',
            'meeting' => 'integer',
            'current_level' => 'integer',
            'approved_levels' => 'array',
            'initial_answer' => 'array',
            'completed_levels' => 'array',
            'attempt_count' => 'integer',
            'level_attempts' => 'array',
            'last_program' => 'array',
            'level_programs' => 'array',
            'predictions' => 'array',
            'level_states' => 'array',
            'lab_state' => 'array',
            'ipo_state' => 'array',
            'exit_ticket_answers' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
