<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class QuizQuestionImport
{
    /**
     * @return list<array{prompt: string, options: array<string, string>, answer: string}>
     */
    public function parse(string $text): array
    {
        if (! mb_check_encoding($text, 'UTF-8') || str_contains($text, "\0")) {
            $this->invalid('Simpan file TXT dengan encoding UTF-8.');
        }

        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/^\h*#[^\n]*$/m', '', $text);
        $blocks = array_values(array_filter(
            array_map('trim', preg_split('/^\h*===\h*$/m', $text)),
            fn (string $block): bool => $block !== '',
        ));

        if (count($blocks) < 1 || count($blocks) > 100) {
            $this->invalid('Masukkan 1 sampai 100 soal dalam satu file.');
        }

        $questions = [];
        $lengths = ['SOAL' => 500, 'A' => 250, 'B' => 250, 'C' => 250, 'D' => 250, 'JAWABAN' => 1];

        foreach ($blocks as $index => $block) {
            $number = $index + 1;
            $fields = [];

            foreach (explode("\n", $block) as $line) {
                if (trim($line) === '') {
                    continue;
                }

                [$key, $value] = array_map('trim', explode(':', $line, 2) + [1 => '']);
                $key = strtoupper($key);
                if (! isset($lengths[$key]) || isset($fields[$key]) || $value === '' || mb_strlen($value) > $lengths[$key]) {
                    $this->invalid("Soal {$number} memiliki baris kosong, berulang, terlalu panjang, atau memakai label yang tidak dikenal.");
                }

                $fields[$key] = $value;
            }

            if (count($fields) !== count($lengths)) {
                $this->invalid("Soal {$number} harus memiliki SOAL, A, B, C, D, dan JAWABAN.");
            }

            $answer = strtoupper($fields['JAWABAN']);
            if (! in_array($answer, ['A', 'B', 'C', 'D'], true)) {
                $this->invalid("Kunci soal {$number} harus berupa A, B, C, atau D.");
            }

            $questions[] = [
                'prompt' => $fields['SOAL'],
                'options' => ['A' => $fields['A'], 'B' => $fields['B'], 'C' => $fields['C'], 'D' => $fields['D']],
                'answer' => $answer,
            ];
        }

        return $questions;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
