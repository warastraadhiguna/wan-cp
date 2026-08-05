@extends('tools.ko-ki.layout')

@section('title', 'Ko-Ki Bot: Komputer Paling Patuh')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <section class="koki-card p-4 p-md-5 text-center">
                <p class="koki-eyebrow mb-2">WAn Ko-Ki</p>
                <h1 class="display-5 koki-title mb-4">Ko-Ki Bot: Komputer Paling Patuh</h1>

                <img
                    class="koki-hero-logo koki-hero-logo-sm mb-4"
                    src="{{ asset('wan-ko-ki.png') }}"
                    alt="WAn Ko-Ki, Koding for Kids"
                >

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="koki-dialogue mb-3">Halo! Aku Ko-Ki Bot.</div>
                        <div class="koki-dialogue mb-4">Hari ini kita akan mencari tahu bagaimana komputer bekerja.</div>

                        <form method="POST" action="{{ route('tools.ko-ki.questions.start') }}">
                            @csrf
                            <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 py-3 fw-bold" type="submit">
                                Mulai Petualangan
                                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
