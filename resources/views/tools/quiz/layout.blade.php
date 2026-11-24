<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Kuis Koding WAn</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}?v={{ filemtime(public_path('css/quiz.css')) }}">
</head>
<body>
    <header class="quiz-nav">
        <a class="quiz-brand" href="{{ route('tools.quiz.index') }}">
            <span aria-hidden="true">&lt;/&gt;</span> Kuis Koding
        </a>
        <a href="{{ route('tools.game') }}">Semua game</a>
    </header>
    <main class="quiz-stage">
        @if (session('status'))
            <p class="quiz-note" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
