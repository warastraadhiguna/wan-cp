<?php

namespace App\Http\Requests;

use App\Services\KoKiAdventureService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKoKiAdventureProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = $this->string('action')->toString();
        $playActions = [
            'factory_sync', 'factory_blend', 'factory_deliver', 'factory_discard', 'factory_powerup',
            'factory_pause', 'factory_resume', 'factory_double_deliver', 'factory_fridge_store',
            'factory_fridge_take',
        ];
        $levelActions = ['factory_start_level', ...$playActions];
        $items = [...array_keys(KoKiAdventureService::INGREDIENTS), ...array_keys(KoKiAdventureService::PRODUCTS)];

        return [
            'action' => ['required', 'string', Rule::in([
                'complete_intro', 'factory_training_replay', 'factory_exit_identify', 'factory_exit_arrange',
                'factory_start_level', ...$playActions,
            ])],
            'level' => [Rule::requiredIf(in_array($action, $levelActions, true)), 'nullable', 'integer', Rule::in(array_keys(KoKiAdventureService::LEVELS))],
            'player' => [Rule::requiredIf(in_array($action, $playActions, true)), 'array:x,y,direction'],
            'player.x' => ['required_with:player', 'integer', 'between:0,'.(KoKiAdventureService::GRID_WIDTH - 1)],
            'player.y' => ['required_with:player', 'integer', 'between:0,'.(KoKiAdventureService::GRID_HEIGHT - 1)],
            'player.direction' => ['required_with:player', 'string', Rule::in(['north', 'east', 'south', 'west'])],
            'inventory' => [Rule::excludeIf(! in_array($action, $playActions, true)), 'present', 'array', 'max:2'],
            'inventory.*' => ['string', Rule::in($items)],
            'blender' => [Rule::requiredIf(in_array($action, $playActions, true)), 'array:ingredients,status,result'],
            'blender.ingredients' => ['exclude_without:blender', 'present', 'array', 'max:4'],
            'blender.ingredients.*' => ['string', Rule::in(array_keys(KoKiAdventureService::INGREDIENTS))],
            'blender.status' => ['exclude_without:blender', 'required', 'string', Rule::in(['empty', 'loaded', 'blending', 'ready'])],
            'blender.result' => ['exclude_without:blender', 'present', 'nullable', 'string', Rule::in(array_keys(KoKiAdventureService::PRODUCTS))],
            'ingredients' => [Rule::requiredIf($action === 'factory_blend'), 'array', 'min:1', 'max:4'],
            'ingredients.*' => ['string', Rule::in(array_keys(KoKiAdventureService::INGREDIENTS))],
            'item' => [
                Rule::requiredIf(in_array($action, ['factory_deliver', 'factory_discard', 'factory_fridge_store'], true)),
                'nullable', 'string', Rule::in($items),
            ],
            'fridge_slot' => [Rule::requiredIf($action === 'factory_fridge_take'), 'nullable', 'integer', Rule::in([0, 1])],
            'powerup' => [
                Rule::requiredIf($action === 'factory_powerup'),
                'nullable', 'string', Rule::in(array_keys(KoKiAdventureService::POWER_UPS)),
            ],
            'answer' => [Rule::requiredIf($action === 'factory_exit_identify'), 'nullable', 'string', Rule::in(['space', 'program', 'jump'])],
            'card_order' => [Rule::requiredIf($action === 'factory_exit_arrange'), 'array', 'size:3'],
            'card_order.*' => ['string', 'distinct', Rule::in(['input', 'process', 'output'])],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'Aksi Factory Rush tidak valid.',
            'level.in' => 'Level Factory Rush tidak dikenal.',
            'player.x.between' => 'Posisi Ko-Ki berada di luar arena.',
            'player.y.between' => 'Posisi Ko-Ki berada di luar arena.',
            'ingredients.min' => 'Masukkan bahan ke blender terlebih dahulu.',
            'inventory.max' => 'Tas Ko-Ki belum dapat membawa sebanyak itu.',
        ];
    }
}
