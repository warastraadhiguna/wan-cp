<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveKoKiCreatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'integer', 'between:0,4'],
            'character' => ['required', Rule::in(['koki', 'cat', 'car'])],
            'theme' => ['required', Rule::in(['green', 'space', 'treasure'])],
            'controls' => ['required', 'array', 'size:3'],
            'controls.left' => ['required', Rule::in(['LEFT', 'RIGHT', 'SPACE'])],
            'controls.right' => ['required', Rule::in(['LEFT', 'RIGHT', 'SPACE'])],
            'controls.jump' => ['required', Rule::in(['LEFT', 'RIGHT', 'SPACE'])],
            'collectible' => ['required', Rule::in(['leaf', 'star', 'gem'])],
            'collect_rule' => ['required', Rule::in(['score_move', 'none'])],
            'obstacle' => ['required', Rule::in(['rock', 'puddle', 'crate'])],
            'obstacle_rule' => ['required', Rule::in(['start', 'minus_one'])],
            'target_score' => ['required', 'integer', Rule::in([3, 5, 10, 15])],
            'tested_win' => ['required', 'boolean'],
            'playtested' => ['sometimes', 'boolean'],
            'playtest_checks' => ['sometimes', 'array', 'size:5'],
            'playtest_checks.control' => ['required_with:playtest_checks', 'boolean'],
            'playtest_checks.collectible' => ['required_with:playtest_checks', 'boolean'],
            'playtest_checks.score' => ['required_with:playtest_checks', 'boolean'],
            'playtest_checks.obstacle' => ['required_with:playtest_checks', 'boolean'],
            'playtest_checks.win' => ['required_with:playtest_checks', 'boolean'],
            'bonuses' => ['sometimes', 'array', 'size:6'],
            'bonuses.second_collectible' => ['required_with:bonuses', 'boolean'],
            'bonuses.special_plus_three' => ['required_with:bonuses', 'boolean'],
            'bonuses.second_obstacle' => ['required_with:bonuses', 'boolean'],
            'bonuses.sound' => ['required_with:bonuses', 'boolean'],
            'bonuses.timer' => ['required_with:bonuses', 'boolean'],
            'bonuses.target' => ['required_with:bonuses', 'boolean'],
        ];
    }
}
