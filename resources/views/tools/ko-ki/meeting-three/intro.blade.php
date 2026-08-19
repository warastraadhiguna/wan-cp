@extends('tools.ko-ki.layout')

@section('title', 'Pendahuluan Rescue Mission')

@section('content')
    <div class="rescue-welcome mx-auto">
        <section class="koki-card p-4 p-md-5">
            <p class="koki-eyebrow mb-2">Pertemuan 3 · Kelas {{ $progress->grade }}</p>
            <h1 class="display-5 koki-title mb-3">Halo, {{ $progress->student_name }}! Ini misi kita hari ini.</h1>
            <p class="lead koki-muted">Taman kota sedang kering. Kamu akan menyusun program agar Ko-Ki mengambil air, melewati rintangan, dan menyiram tanaman.</p>

            <div class="row g-3 my-3">
                @foreach ([['🎯','Jelas','Ko-Ki tahu tindakan yang diminta.'],['📍','Tepat','Command sesuai tempat dan arah.'],['🧩','Lengkap','Tidak ada langkah penting yang hilang.'],['🔢','Berurutan','Command dijalankan dari awal sampai akhir.']] as [$icon,$title,$copy])
                    <div class="col-6 col-lg-3"><div class="rescue-panel h-100 text-center"><div class="display-5">{{ $icon }}</div><strong>{{ $title }}</strong><p class="small mb-0 mt-1">{{ $copy }}</p></div></div>
                @endforeach
            </div>

            <div class="koki-dialogue mb-4"><strong>Ingat:</strong> Ko-Ki tidak menebak keinginan kita. Ko-Ki menjalankan command satu per satu, sesuai urutannya.</div>
            <form method="POST" action="{{ route('tools.ko-ki.meeting-three.intro.complete') }}">
                @csrf
                <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Aku Siap Menyusun Program <i class="bi bi-arrow-right ms-1"></i></button>
            </form>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/rescue-mission.css') }}?v={{ filemtime(public_path('css/ko-ki/rescue-mission.css')) }}">
@endpush
