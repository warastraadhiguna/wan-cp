<x-filament-panels::page>
    <x-filament::section :heading="$record->title">
        @if ($record->active_slot)
            <p>Tutup pertemuan sebelum mengunggah bank soal baru.</p>
        @else
            <form method="POST" action="{{ route('admin.quiz-banks.store', $record) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <p>
                    Siapkan 1 sampai 100 soal dalam file TXT UTF-8. Setiap soal berisi
                    <code>SOAL</code>, <code>A</code>, <code>B</code>, <code>C</code>,
                    <code>D</code>, dan <code>JAWABAN</code>. Pisahkan soal dengan <code>===</code>.
                </p>
                <x-filament::link :href="route('admin.quiz-banks.example')">Unduh contoh format TXT</x-filament::link>
                @if ($record->currentBank)
                    <p>Unggahan ini membuat bank soal dan peringkat baru untuk pertemuan ini.</p>
                @endif
                <div>
                    <label for="quiz-file">File soal TXT</label>
                    <x-filament::input.wrapper class="mt-1 max-w-lg">
                        <x-filament::input id="quiz-file" name="file" type="file" accept=".txt,text/plain" required />
                    </x-filament::input.wrapper>
                    @error('file') <p role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="quiz-seconds">Waktu menjawab per soal (5–120 detik)</label>
                    <x-filament::input.wrapper class="mt-1 max-w-xs">
                        <x-filament::input id="quiz-seconds" name="seconds_per_question" type="number" min="5" max="120"
                            :value="old('seconds_per_question', $record->currentBank?->seconds_per_question ?? 20)" required />
                    </x-filament::input.wrapper>
                    @error('seconds_per_question') <p role="alert">{{ $message }}</p> @enderror
                </div>
                <x-filament::button type="submit">Simpan bank soal</x-filament::button>
            </form>
        @endif
        <div class="mt-5">
            <x-filament::link :href="\App\Filament\Resources\QuizMeetings\QuizMeetingResource::getUrl('index')">
                Kembali ke Pertemuan Kuis
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-panels::page>
