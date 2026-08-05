@extends('tools.ko-ki.layout')

@section('title', 'Jawaban Tersimpan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <section class="koki-card p-4 p-md-5 text-center">
                <div class="display-1 mb-4" aria-hidden="true">✅</div>
                <h1 class="display-6 koki-title mb-3">Jawabanmu sudah disimpan.</h1>
                <p class="lead koki-muted mb-4">Sekarang kita buktikan melalui eksperimen.</p>

                <form method="POST" action="{{ route('tools.ko-ki.activity.start') }}">
                    @csrf
                    <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 py-3 fw-bold" type="submit">
                        Lanjut ke Eksperimen
                        <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
