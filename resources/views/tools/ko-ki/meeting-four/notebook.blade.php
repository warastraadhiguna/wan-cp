@extends('tools.ko-ki.layout')

@section('title', 'Detective Notebook')

@section('content')
    <div class="detective-shell mx-auto" data-detective-notebook data-progress-id="{{ $progress->getKey() }}" data-state='@json($detectiveState)' data-sync-url="{{ route('tools.ko-ki.meeting-four.sync') }}" data-map-url="{{ route('tools.ko-ki.meeting-four.map') }}">
        <header class="text-center mb-4"><p class="koki-eyebrow mb-1">Semua Berkas Kasus Terkumpul</p><h1 class="display-4 koki-title">📔 Detective Notebook</h1><p class="lead koki-muted">Catatan Detektif {{ $progress->student_name }} · Kelas {{ $progress->grade }}</p><div class="detective-save-status mx-auto" data-save-status>✓ Tersimpan</div></header>

        <section class="koki-card p-4 p-md-5 detective-notebook">
            <h2 class="h3 fw-bold text-center mb-4">Bug yang Sudah Kamu Temukan</h2>
            <div class="detective-notebook-bugs">
                @foreach (['🧭 Wrong Direction','🥤 Extra Command','🛗 Missing Command','🕺 Wrong Order','🎮 Wrong Input','🚦 Wrong Output','🪴 Wrong Action','🚪 Extra Action','📦 Missing Middle Step','🔌 Multiple Input Mapping'] as $bug)
                    <div>🐞✅ <strong>{{ $bug }}</strong></div>
                @endforeach
                @foreach (['🧊 Wrong Cooling Output','🎬 Setup Order','🖨️ Duplicate Command','🚁 Missing Mission Step','🏭 Production Order'] as $bug)
                    <div>🐞✅ <strong>{{ $bug }}</strong></div>
                @endforeach
            </div>

            <div class="detective-debug-flow my-5" aria-label="Alur debugging">
                @foreach (['▶️'=>'Jalankan','👀'=>'Amati','🔎'=>'Temukan masalah','🛠️'=>'Perbaiki','🧪'=>'Coba lagi'] as $icon => $step)
                    <div><span>{{ $icon }}</span><strong>{{ $loop->iteration }}. {{ $step }}</strong></div>@if (! $loop->last)<i>→</i>@endif
                @endforeach
            </div>

            <div class="detective-definition text-center"><p class="koki-eyebrow mb-1">Sekarang kamu mengenalnya</p><h2>DEBUGGING</h2><p>“Mencari dan memperbaiki kesalahan pada program.”</p></div>
            <blockquote class="koki-dialogue text-center my-4">“Program belum berhasil? Cari bug-nya, perbaiki, lalu coba lagi!”</blockquote>

            <div class="detective-badge-award text-center" data-notebook-award @if (! $detectiveState['notebook_completed']) hidden @endif><div class="display-1">🏅</div><p class="koki-eyebrow mb-1">BADGE TERBUKA</p><h2 class="display-5 koki-title">Bug Detective</h2><p class="lead">Kamu menyelesaikan 15 kasus dan Final Case, serta memperoleh ⭐ <span data-notebook-stars>{{ $detectiveState['total_stars'] }}</span>/48.</p><a href="{{ route('tools.ko-ki.index') }}" class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Kembali ke Daftar Materi</a></div>
                <div class="detective-mini-challenge" data-notebook-challenge @if ($detectiveState['notebook_completed']) hidden @endif>
                    <p class="koki-eyebrow mb-1">Mini Challenge Terakhir</p><h2 class="h3 fw-bold">Perbaiki program pendek ini</h2><p>Ko-Ki harus berbelok ke kanan menuju bendera.</p>
                    <div class="detective-mini-program"><span>1</span><b>MAJU</b><span>2</span><b class="is-bug">PUTAR KIRI 📌</b><span>3</span><b>MAJU</b></div>
                    <div class="alert alert-warning rounded-4 mt-3" data-notebook-feedback hidden></div>
                    <form method="POST" action="{{ route('tools.ko-ki.meeting-four.notebook.complete') }}" class="mt-3" data-notebook-form>
                        @csrf
                        <fieldset><legend class="h5 fw-bold">Ganti command nomor 2 menjadi:</legend><div class="row g-2">
                            @foreach (['TURN_LEFT'=>'PUTAR KIRI','TURN_RIGHT'=>'PUTAR KANAN','MOVE_FORWARD'=>'MAJU'] as $value => $label)
                                <div class="col-md-4"><div class="koki-answer"><input id="fix-{{ $value }}" type="radio" name="fix" value="{{ $value }}" required><label for="fix-{{ $value }}">{{ $label }}</label></div></div>
                            @endforeach
                        </div></fieldset>
                        <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold mt-3">Test Fix Terakhir</button>
                    </form>
                </div>
        </section>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="{{ asset('css/ko-ki/detective.css') }}?v={{ filemtime(public_path('css/ko-ki/detective.css')) }}">@endpush
@push('scripts')<script type="module" src="{{ asset('js/ko-ki/detective-notebook.js') }}?v={{ filemtime(public_path('js/ko-ki/detective-notebook.js')) }}"></script>@endpush
