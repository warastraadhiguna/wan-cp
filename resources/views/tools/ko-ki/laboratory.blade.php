@extends('tools.ko-ki.layout')

@section('title', 'Laboratorium Perintah Aneh')

@section('content')
    <div
        data-ko-ki-laboratory
        data-configuration='@json($laboratory)'
        data-state='@json($labState)'
        data-save-url="{{ route('tools.ko-ki.laboratory.update') }}"
    >
        <div class="text-center mb-4">
            <p class="koki-eyebrow mb-2">Setelah Tujuh Misi</p>
            <h1 class="display-5 koki-title mb-2">Laboratorium Perintah Aneh</h1>
            <p class="lead koki-muted mb-0">Coba berikan perintah yang biasa dan yang belum dikenal Ko-Ki.</p>
        </div>

        <section class="koki-card p-4 p-md-5" data-strange-stage>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="koki-grid" data-lab-grid aria-label="Lintasan Ko-Ki Bot"></div>
                    <p class="alert alert-light border rounded-4 text-center fw-semibold mt-3 mb-0" data-lab-dialog aria-live="polite">
                        Pilih satu perintah untuk Ko-Ki.
                    </p>
                </div>
                <div class="col-lg-6">
                    <p class="koki-eyebrow mb-2">Percobaan bebas</p>
                    <h2 class="h3 fw-bold mb-3">Apa yang dapat dilakukan Ko-Ki?</h2>
                    <div class="d-grid gap-2" data-strange-commands></div>
                </div>
            </div>

            <div class="border-top mt-5 pt-4" data-strange-question hidden>
                <p class="koki-eyebrow mb-2">Pertanyaan laboratorium</p>
                <h2 class="h3 fw-bold mb-4" data-strange-prompt></h2>
                <div class="row g-3" data-strange-options></div>
                <p class="alert rounded-4 mt-3 mb-0" data-strange-feedback aria-live="polite" hidden></p>
            </div>
        </section>

        <section class="koki-card p-4 p-md-5" data-classification-stage hidden>
            <div class="text-center mb-4">
                <p class="koki-eyebrow mb-2">Aktivitas klasifikasi</p>
                <h2 class="h3 fw-bold mb-2">Siapa yang melakukan ini?</h2>
                <p class="koki-muted mb-1"><span data-classification-progress>1 dari 10</span></p>
            </div>

            <div class="koki-dialogue text-center mb-4" data-classification-statement></div>

            <div class="row g-3 justify-content-center">
                <div class="col-sm-6">
                    <button class="btn btn-primary koki-btn-primary koki-command-button w-100" type="button" data-category="human">
                        <i class="bi bi-person-fill me-2" aria-hidden="true"></i> MANUSIA
                    </button>
                </div>
                <div class="col-sm-6">
                    <button class="btn btn-outline-primary koki-command-button w-100" type="button" data-category="computer">
                        <i class="bi bi-cpu-fill me-2" aria-hidden="true"></i> KOMPUTER
                    </button>
                </div>
            </div>

            <div class="alert rounded-4 text-center mt-4 mb-0" data-classification-feedback aria-live="polite" hidden>
                <p class="mb-3" data-classification-feedback-message></p>
                <button class="btn btn-success rounded-pill fw-bold px-5" type="button" data-classification-next hidden>
                    Lanjut <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </button>
            </div>
        </section>

        <section class="koki-card p-4 p-md-5 text-center" data-conclusion-stage hidden>
            <div class="display-2 mb-3" aria-hidden="true">🧪</div>
            <p class="koki-eyebrow mb-2">Kesimpulan</p>
            <h2 class="h3 fw-bold mb-4">Laboratorium selesai</h2>

            <div class="row g-3 text-start mb-5">
                <div class="col-md-4">
                    <div class="koki-step fw-semibold">Manusia menentukan tujuan dan membuat program.</div>
                </div>
                <div class="col-md-4">
                    <div class="koki-step fw-semibold">Komputer membantu menjalankan program.</div>
                </div>
                <div class="col-md-4">
                    <div class="koki-step fw-semibold">Manusia dan komputer memiliki kemampuan yang berbeda.</div>
                </div>
            </div>

            <div class="text-start border-top pt-4">
                <p class="koki-eyebrow mb-2">Exit ticket</p>
                <h3 class="h4 fw-bold mb-4">Pilih jawaban yang sesuai</h3>
                <form method="POST" action="{{ route('tools.ko-ki.finish') }}">
                    @csrf

                    @foreach ([
                        'q1' => 'Komputer dapat menebak semua keinginan manusia.',
                        'q2' => 'Program adalah kumpulan perintah untuk komputer.',
                        'q3' => 'Programmer hanya memperbaiki komputer rusak.',
                    ] as $questionId => $statement)
                        <fieldset class="border rounded-4 p-3 mb-3">
                            <legend class="h6 fw-bold">{{ $loop->iteration }}. {{ $statement }}</legend>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach (['true' => 'Benar', 'not_quite' => 'Belum tepat'] as $value => $label)
                                    <input
                                        class="btn-check"
                                        id="{{ $questionId }}-{{ $value }}"
                                        name="exit_ticket_answers[{{ $questionId }}]"
                                        type="radio"
                                        value="{{ $value }}"
                                        @checked(old("exit_ticket_answers.{$questionId}") === $value)
                                        required
                                    >
                                    <label class="btn btn-outline-primary rounded-pill px-4" for="{{ $questionId }}-{{ $value }}">{{ $label }}</label>
                                @endforeach
                            </div>
                            @error("exit_ticket_answers.{$questionId}")
                                <p class="text-danger small mt-2 mb-0">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    @endforeach

                    <div class="alert alert-info rounded-4 mb-4">
                        <p class="fw-semibold mb-1">Programmer membuat, menguji, dan memperbaiki program.</p>
                        <p class="fw-semibold mb-0">Perangkat komputer biasanya diperbaiki oleh teknisi.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="reflection">Mengapa komputer membutuhkan program?</label>
                        <textarea
                            class="form-control @error('reflection') is-invalid @enderror"
                            id="reflection"
                            name="reflection"
                            rows="4"
                            maxlength="200"
                            data-reflection
                            required
                        >{{ old('reflection', $progress->reflection) }}</textarea>
                        <div class="d-flex justify-content-between gap-3 mt-1">
                            @error('reflection')
                                <span class="text-danger small">{{ $message }}</span>
                            @else
                                <span class="small koki-muted">Maksimal 200 karakter</span>
                            @enderror
                            <span class="small koki-muted"><span data-reflection-count>0</span>/200</span>
                        </div>
                    </div>

                    <div class="koki-answer mb-4">
                        <input
                            id="reflection-choice"
                            name="reflection_choice"
                            type="radio"
                            value="Agar tahu apa yang harus dilakukan"
                            @checked(old('reflection_choice', $progress->reflection_choice) === 'Agar tahu apa yang harus dilakukan')
                            required
                        >
                        <label for="reflection-choice">Agar tahu apa yang harus dilakukan</label>
                        @error('reflection_choice')
                            <p class="text-danger small mt-2 mb-0">{{ $message }}</p>
                        @enderror
                    </div>

                    @error('exit_ticket_answers')
                        <div class="alert alert-danger rounded-4">{{ $message }}</div>
                    @enderror

                    <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold" type="submit">
                        Selesaikan Pertemuan
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/laboratory.js') }}?v={{ filemtime(public_path('js/ko-ki/laboratory.js')) }}"></script>
@endpush
