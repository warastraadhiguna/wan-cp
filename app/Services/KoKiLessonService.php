<?php

namespace App\Services;

class KoKiLessonService
{
    /**
     * @return array<int, array{prompt: string, answers: array<string, string>}>
     */
    public function questions(): array
    {
        return [
            1 => [
                'prompt' => 'Apakah komputer lebih pintar daripada manusia?',
                'answers' => [
                    'computer' => 'Komputer lebih pintar',
                    'human' => 'Manusia lebih pintar',
                    'different' => 'Keduanya memiliki kemampuan berbeda',
                    'unknown' => 'Aku belum tahu',
                ],
            ],
            2 => [
                'prompt' => 'Apakah komputer dapat mengetahui keinginan kita tanpa diberi tahu?',
                'answers' => [
                    'yes' => 'Bisa',
                    'no' => 'Tidak bisa',
                    'sometimes' => 'Kadang-kadang',
                    'unknown' => 'Aku belum tahu',
                ],
            ],
            3 => [
                'prompt' => 'Mengapa karakter gim dapat bergerak?',
                'answers' => [
                    'alive' => 'Karena karakter hidup',
                    'program' => 'Karena ada program',
                    'guessing' => 'Karena komputer menebak',
                    'moving-screen' => 'Karena layar bergerak sendiri',
                ],
            ],
            4 => [
                'prompt' => 'Siapa yang membuat program?',
                'answers' => [
                    'programmer' => 'Programmer',
                    'computer' => 'Komputer',
                    'robot' => 'Robot',
                    'game-player' => 'Pemain gim',
                ],
            ],
        ];
    }

    /**
     * @return array{prompt: string, answers: array<string, string>}|null
     */
    public function question(int $number): ?array
    {
        return $this->questions()[$number] ?? null;
    }

    public function nextQuestionNumber(?array $answers): ?int
    {
        $answers ??= [];

        foreach (array_keys($this->questions()) as $number) {
            if (! array_key_exists($number, $answers)) {
                return $number;
            }
        }

        return null;
    }
}
