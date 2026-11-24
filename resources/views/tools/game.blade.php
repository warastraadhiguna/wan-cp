<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Game Koding | WAn</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f4f7fc;
            color: #172033;
            font: 1rem/1.5 system-ui, sans-serif;
        }
        .container {
            width: min(72rem, 100% - 2rem);
            margin-inline: auto;
        }
        header {
            background: #172033;
            color: #fff;
            padding: 1.5rem 0 2rem;
        }
        header a { color: #d7e7ff; }
        h1 {
            margin: 1.25rem 0 0;
            font-size: clamp(2rem, 5vw, 3rem);
            line-height: 1.2;
        }
        .games {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 15.5rem), 1fr));
            gap: 1rem;
            padding: 0;
            margin: 1.5rem 0 2.5rem;
            list-style: none;
        }
        .game {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            height: 100%;
            min-height: 5rem;
            padding: 1rem;
            background: #fff;
            border: 1px solid #d3dff0;
            border-radius: .85rem;
            color: #2445a5;
            font-weight: 650;
            text-decoration: none;
        }
        .game:hover, .game:focus-visible {
            background: #eaf1ff;
            border-color: #3157d5;
        }
        a:focus-visible { outline: 3px solid #5283dc; outline-offset: 3px; }
    </style>
</head>
<body>
    @php
        $games = [
            'Brankas Misterius' => 'Brankas-Misterius-5-Kelompok/index.html',
            'Detektif Data' => 'Detektif/index.html',
            'Siapa Aku? (Eat Bulaga)' => 'Eat bulaga/eat_bulaga_tebak_kata_5_kelompok.html',
            'Kode yang Dihapus (Huruf Hilang)' => 'Kata-Huruf Hilang/index.html',
            'Memory Match' => 'Memory Match/game_memory_match_3_level.html',
            'Misi Urutan Algoritma' => 'Misi-Urutan-Algoritma/index.html',
            'Detektif Bug (Cari Perbedaan)' => 'Perbedaan/index.html',
            'Robot Sinyal' => 'Robot-Sinyal-5-Kelompok/index.html',
            'Secret Code: Huruf Bergeser' => 'Secret-Code-Huruf-Bergeser/Secret-Code-Huruf-Bergeser/index.html',
            'Secret Code: Kode Simbol' => 'Secret-Code-Kode-Simbol/index.html',
            'Secret Code: Misi 1' => 'SECRET-CODE-MISI/Secret-Code-Misi-1/index.html',
            'Simon Says Warna' => 'simon says warna/simon_says_warna_5_kelompok.html',
            'Tebak Password Mesin' => 'Tebak Password/index.html',
            'TTS Koding Interaktif' => 'tts/tts_koding_for_kids_interaktif.html',
            'Robot Terjebak! (WAn Ko-Ki)' => 'wan ko ki/index.html',
            'Word Hacker' => 'Word Hacker/index.html',
        ];
    @endphp

    <header>
        <div class="container">
            <a href="{{ route('home') }}">← Beranda</a>
            <h1>Game Koding</h1>
        </div>
    </header>

    <main class="container">
        <ul class="games">
            @foreach ($games as $title => $path)
                @php($encodedPath = implode('/', array_map('rawurlencode', explode('/', 'Game Koding/'.$path))))
                <li>
                    <a class="game" href="{{ url($encodedPath) }}">
                        <span>{{ $title }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </main>
</body>
</html>
