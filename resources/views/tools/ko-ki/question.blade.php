@extends('tools.ko-ki.layout')

@section('title', 'Pertanyaan '.$questionNumber)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <section class="koki-card p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                    <p class="koki-eyebrow mb-0">Pertanyaan awal</p>
                    <strong>{{ $questionNumber }} dari {{ $questionCount }}</strong>
                </div>

                <div class="progress koki-progress mb-4" role="progressbar" aria-label="Progress pertanyaan" aria-valuenow="{{ $questionNumber }}" aria-valuemin="1" aria-valuemax="{{ $questionCount }}">
                    <div class="progress-bar" style="width: {{ ($questionNumber / $questionCount) * 100 }}%"></div>
                </div>

                <h1 class="h2 koki-title mb-4">{{ $question['prompt'] }}</h1>

                <form method="POST" action="{{ route('tools.ko-ki.questions.answer', $questionNumber) }}">
                    @csrf

                    <fieldset>
                        <legend class="visually-hidden">Pilih satu jawaban</legend>
                        <div class="d-grid gap-3 mb-4">
                            @foreach ($question['answers'] as $value => $label)
                                <div class="koki-answer">
                                    <input
                                        id="answer-{{ $value }}"
                                        name="answer"
                                        type="radio"
                                        value="{{ $value }}"
                                        @checked(old('answer') === $value)
                                    >
                                    <label for="answer-{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    @error('answer')
                        <div class="alert alert-danger rounded-4" role="alert">{{ $message }}</div>
                    @enderror

                    <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill w-100 py-3 fw-bold" type="submit">
                        {{ $questionNumber === $questionCount ? 'Simpan Jawaban' : 'Simpan dan Lanjut' }}
                        <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
