@extends('tools.ko-ki.layout')

@section('title', 'Pertemuan 1')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <section class="koki-card overflow-hidden">
                <div class="row g-0">
                    <div class="col-lg-7 p-4 p-md-5">
                        <p class="koki-eyebrow mb-2">Koding for Kids · Pertemuan 1</p>
                        <h1 class="display-5 koki-title mb-3">Halo! Kenalan dengan Ko-Ki Bot</h1>
                        <p class="lead koki-muted mb-4">
                            Ko-Ki adalah komputer paling patuh. Ia menjalankan instruksi sesuai urutan yang kamu berikan.
                        </p>

                        @if ($progress)
                            <div class="alert alert-light border rounded-4 mb-4">
                                <strong>Progress ditemukan.</strong><br>
                                {{ $progress->student_name }}, kelas {{ $progress->grade }}, dapat melanjutkan dari fase terakhir.
                                <div class="mt-3">
                                    <a class="btn btn-outline-primary rounded-pill px-4" href="{{ $resumeUrl }}">
                                        Lanjutkan progress
                                    </a>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tools.ko-ki.start') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="student_name">Nama panggilan</label>
                                <input
                                    class="form-control @error('student_name') is-invalid @enderror"
                                    id="student_name"
                                    name="student_name"
                                    type="text"
                                    value="{{ old('student_name') }}"
                                    maxlength="40"
                                    autocomplete="nickname"
                                    placeholder="Contoh: Naya"
                                    required
                                    autofocus
                                >
                                @error('student_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="grade">Kelas</label>
                                <select class="form-select @error('grade') is-invalid @enderror" id="grade" name="grade" required>
                                    <option value="">Pilih kelas</option>
                                    <option value="4" @selected(old('grade') === '4')>Kelas 4</option>
                                    <option value="5" @selected(old('grade') === '5')>Kelas 5</option>
                                </select>
                                @error('grade')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold" type="submit">
                                Masuk ke pembukaan <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>

                    <div class="col-lg-5 p-4 p-md-5 bg-primary-subtle d-flex align-items-center">
                        <div>
                            <img
                                class="koki-hero-logo mb-4"
                                src="{{ asset('wan-ko-ki.png') }}"
                                alt="WAn Ko-Ki, Koding for Kids"
                            >
                            <h2 class="h4 fw-bold">Hari ini kita belajar</h2>
                            <ul class="mb-0 ps-3 koki-muted">
                                <li class="mb-2">Memberi instruksi yang jelas</li>
                                <li class="mb-2">Menyusun langkah secara berurutan</li>
                                <li>Memeriksa dan memperbaiki instruksi</li>
                            </ul>

                            <hr class="my-4">
                            <h2 class="h5 fw-bold">Rencana waktu · maksimal 60 menit</h2>
                            <ul class="small mb-0 ps-3 koki-muted">
                                <li>Pembukaan dan pertanyaan · 8 menit</li>
                                <li>Sandbox · 7 menit</li>
                                <li>Empat misi · 30 menit</li>
                                <li>Laboratorium · 10 menit</li>
                                <li>Exit ticket dan refleksi · 5 menit</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
