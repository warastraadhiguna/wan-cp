@extends('tools.ko-ki.layout')

@section('title', 'Final Reveal — Factory Rush')

@section('content')
    @php
        $recipeId = $factoryState['final_recipe'] ?? 'apple_juice';
        $ingredients = $factory['recipes'][$recipeId];
        $product = $factory['products'][$recipeId];
        $ingredientText = collect($ingredients)->map(fn (string $id): string => $factory['ingredients'][$id]['label'])->join(' + ');
        $ingredientIcons = collect($ingredients)->map(fn (string $id): string => $factory['ingredients'][$id]['icon'])->join(' + ');
    @endphp
    <div
        class="factory-finale"
        data-factory-finale
        data-save-url="{{ route('tools.ko-ki.meeting-two.update') }}"
        data-state='@json($factoryState)'
    >
        <section class="koki-card p-4 p-md-5 text-center">
            <p class="koki-eyebrow mb-1">Final Reveal</p>
            <h1 class="display-5 koki-title mb-2">Pabrik Berhasil Dijalankan!</h1>
            <p class="koki-muted mb-4">Ini salah satu order yang benar-benar kamu buat.</p>

            <div class="factory-final-recipe mx-auto mb-4">
                <div><span>{{ $ingredientIcons }}</span><strong>{{ $ingredientText }}</strong></div><b>↓</b>
                <div><span>⚙️</span><strong>Blender</strong></div><b>↓</b>
                <div><span>{{ $product['icon'] }}</span><strong>{{ $product['label'] }}</strong></div>
            </div>
            <div class="factory-final-ipo mb-5"><strong>INPUT</strong><b>→</b><strong>PROSES</strong><b>→</b><strong>OUTPUT</strong></div>

            <div class="factory-computer-demo mx-auto mb-4">
                <div class="factory-demo-screen"><span class="factory-demo-bot" data-demo-bot>🤖</span></div>
                <div>
                    <p class="h5 fw-bold mb-2">Tekan SPACE agar Ko-Ki melompat</p>
                    <button class="btn btn-primary btn-lg rounded-pill px-5 fw-bold" type="button" data-demo-space>SPACE</button>
                </div>
            </div>
            <div class="factory-ipo-labels mb-3">
                <div><small>INPUT</small><strong>Space ditekan</strong></div>
                <div><small>PROSES</small><strong>Program membaca tombol</strong></div>
                <div><small>OUTPUT</small><strong>Ko-Ki Bot melompat</strong></div>
            </div>
            <p class="lead fw-bold factory-final-message mx-auto mb-5">Mesin dan komputer berbeda, tetapi keduanya dapat menerima input, melakukan proses, dan menghasilkan output.</p>

            <div class="factory-exit-challenges mx-auto">
                <article data-challenge-identify {{ $factoryState['exit_challenges']['identify'] ? 'hidden' : '' }}>
                    <p class="koki-eyebrow mb-1">Exit Challenge 1 dari 2</p>
                    <h2 class="h4 fw-bold mb-3">Klik bagian INPUT pada contoh gim</h2>
                    <div class="factory-challenge-options">
                        <button type="button" data-identify="space">⌨️ Space ditekan</button>
                        <button type="button" data-identify="program">⚙️ Program membaca tombol</button>
                        <button type="button" data-identify="jump">🤖 Ko-Ki melompat</button>
                    </div>
                </article>

                <article data-challenge-arrange {{ !$factoryState['exit_challenges']['identify'] || $factoryState['exit_challenges']['arrange'] ? 'hidden' : '' }}>
                    <p class="koki-eyebrow mb-1">Exit Challenge 2 dari 2</p>
                    <h2 class="h4 fw-bold mb-2">Klik tiga kartu sesuai urutan</h2>
                    <p class="small koki-muted">Mulai dari yang masuk sampai hasilnya.</p>
                    <div class="factory-arrange-result" data-arrange-result><span>1</span><span>2</span><span>3</span></div>
                    <div class="factory-challenge-options is-cards">
                        <button type="button" data-card="output">OUTPUT</button>
                        <button type="button" data-card="input">INPUT</button>
                        <button type="button" data-card="process">PROSES</button>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill mt-2" type="button" data-reset-cards>Ulangi Susunan</button>
                </article>

                <p class="factory-challenge-feedback" data-finale-feedback role="status" aria-live="polite"></p>
            </div>

            <div class="factory-final-badge" data-final-badge {{ $factoryState['completed'] ? '' : 'hidden' }}>
                <div class="factory-badge-medal" aria-hidden="true">🏅</div>
                <p class="koki-eyebrow mb-1">Badge diperoleh</p>
                <h2 class="display-6 fw-bold">Factory Master</h2>
                <p>Hebat, {{ $progress->student_name }}! Pertemuan 2 selesai.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a class="btn btn-primary btn-lg rounded-pill px-5 fw-bold" href="{{ route('tools.ko-ki.index') }}">Kembali ke Daftar Materi</a>
                    <a class="btn btn-outline-primary btn-lg rounded-pill px-4 fw-bold" href="{{ route('tools.ko-ki.meeting-two.map') }}">Mainkan Lagi</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/factory-rush.css') }}?v={{ filemtime(public_path('css/ko-ki/factory-rush.css')) }}">
@endpush

@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/factory-finale.js') }}?v={{ filemtime(public_path('js/ko-ki/factory-finale.js')) }}"></script>
@endpush
