<?php

namespace App\Services;

use App\Models\KoKiProgress;
use Illuminate\Validation\ValidationException;

class KoKiLaboratoryService
{
    public const CORRECT_STRANGE_ANSWER = 'not_created';

    public const STRANGE_COMMANDS = [
        'FLY_TO_MOON' => [
            'label' => 'Terbang ke bulan',
            'implemented' => false,
            'prompt' => 'Mengapa Ko-Ki Bot tidak terbang ke bulan?',
            'dislike_label' => 'Tidak suka bulan',
        ],
        'BECOME_DRAGON' => [
            'label' => 'Menjadi naga',
            'implemented' => false,
            'prompt' => 'Mengapa Ko-Ki Bot tidak menjadi naga?',
            'dislike_label' => 'Tidak suka naga',
        ],
        'EAT_ICE_CREAM' => [
            'label' => 'Makan es krim',
            'implemented' => false,
            'prompt' => 'Mengapa Ko-Ki Bot tidak makan es krim?',
            'dislike_label' => 'Tidak suka es krim',
        ],
        'SING' => [
            'label' => 'Menyanyi',
            'implemented' => false,
            'prompt' => 'Mengapa Ko-Ki Bot tidak menyanyi?',
            'dislike_label' => 'Tidak suka menyanyi',
        ],
        'MOVE_FORWARD' => ['label' => 'Maju', 'implemented' => true],
    ];

    public const STRANGE_ANSWER_OPTIONS = [
        'lazy' => 'Sedang malas',
        'dislikes_command' => null,
        self::CORRECT_STRANGE_ANSWER => 'Perintahnya belum dibuat',
        'broken' => 'Komputernya rusak',
    ];

    public const CLASSIFICATIONS = [
        ['id' => 'set_goal', 'statement' => 'Menentukan tujuan', 'category' => 'human'],
        ['id' => 'make_rules', 'statement' => 'Membuat aturan', 'category' => 'human'],
        ['id' => 'choose_instructions', 'statement' => 'Memilih instruksi', 'category' => 'human'],
        ['id' => 'check_results', 'statement' => 'Memeriksa hasil', 'category' => 'human'],
        ['id' => 'fix_program', 'statement' => 'Memperbaiki program', 'category' => 'human'],
        ['id' => 'receive_data', 'statement' => 'Menerima data', 'category' => 'computer'],
        ['id' => 'run_rules', 'statement' => 'Menjalankan aturan', 'category' => 'computer'],
        ['id' => 'repeat_work', 'statement' => 'Mengulang pekerjaan', 'category' => 'computer'],
        ['id' => 'store_information', 'statement' => 'Menyimpan informasi', 'category' => 'computer'],
        ['id' => 'show_results', 'statement' => 'Menampilkan hasil', 'category' => 'computer'],
    ];

    public function clientConfiguration(): array
    {
        return [
            'commands' => array_map(
                fn (array $command, string $id): array => ['id' => $id, ...$command],
                self::STRANGE_COMMANDS,
                array_keys(self::STRANGE_COMMANDS),
            ),
            'answer_options' => array_map(
                fn (?string $label, string $value): array => compact('value', 'label'),
                self::STRANGE_ANSWER_OPTIONS,
                array_keys(self::STRANGE_ANSWER_OPTIONS),
            ),
            'classifications' => array_map(
                fn (array $item): array => [
                    'id' => $item['id'],
                    'statement' => $item['statement'],
                ],
                self::CLASSIFICATIONS,
            ),
        ];
    }

    public function update(KoKiProgress $progress, array $data): KoKiProgress
    {
        $state = $this->state($progress);

        if ($data['action'] === 'try_command') {
            $state['tried_commands'][] = $data['command'];
            $state['tried_commands'] = array_values(array_unique($state['tried_commands']));

            if (! self::STRANGE_COMMANDS[$data['command']]['implemented']) {
                $state['selected_strange_command'] = $data['command'];
            }
        }

        if ($data['action'] === 'answer_question') {
            if ($state['selected_strange_command'] === null) {
                throw ValidationException::withMessages([
                    'strange_answer' => 'Pilih satu perintah yang belum dibuat terlebih dahulu.',
                ]);
            }

            $state['strange_answer'] = $data['strange_answer'];
            $state['strange_completed'] = $data['strange_answer'] === self::CORRECT_STRANGE_ANSWER;
        }

        if ($data['action'] === 'classify') {
            if (! $state['strange_completed']) {
                throw ValidationException::withMessages([
                    'action' => 'Selesaikan pertanyaan laboratorium terlebih dahulu.',
                ]);
            }

            $expectedItem = self::CLASSIFICATIONS[$state['classification_index']] ?? null;

            if ($expectedItem === null || $expectedItem['id'] !== $data['classification_item']) {
                throw ValidationException::withMessages([
                    'classification_item' => 'Pernyataan klasifikasi tidak sesuai dengan progress saat ini.',
                ]);
            }

            $state['classification_answers'][$data['classification_item']] = $data['classification_category'];

            if ($data['classification_category'] === $expectedItem['category']) {
                $state['classification_index']++;
            }

            $state['classification_completed'] = $state['classification_index'] >= count(self::CLASSIFICATIONS);
            $state['completed'] = $state['classification_completed'];
        }

        $progress->forceFill([
            'last_phase' => 'laboratory',
            'lab_state' => $state,
        ])->save();

        return $progress->refresh();
    }

    public function state(KoKiProgress $progress): array
    {
        return [
            'tried_commands' => [],
            'selected_strange_command' => null,
            'strange_answer' => null,
            'strange_completed' => false,
            'classification_answers' => [],
            'classification_index' => 0,
            'classification_completed' => false,
            'completed' => false,
            ...($progress->lab_state ?? []),
        ];
    }

    public function correctCategory(string $itemId): ?string
    {
        foreach (self::CLASSIFICATIONS as $item) {
            if ($item['id'] === $itemId) {
                return $item['category'];
            }
        }

        return null;
    }
}
