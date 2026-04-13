@extends('front-end.app.app-home-asset', ['title' => 'Page Introuvable', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3'])

@section('content')
<main class="min-h-screen flex items-center justify-center p-6 bg-n10 dark:bg-bg3">
    <div class="text-center">
        <h1 class="text-9xl font-black text-primary/20">404</h1>
        <div class="mt-[-40px]">
            <h2 class="text-3xl font-bold text-n500 dark:text-n200 mb-4">Oups ! Page introuvable</h2>
            <p class="text-n300 dark:text-n400 mb-8 max-w-md mx-auto">
                La page ou le dossier que vous recherchez n'existe pas ou a été déplacé. 
            </p>
            <a href="{{ url('/') }}" class="bg-primary hover:bg-primary/90 text-white px-8 py-3 rounded-xl font-bold transition-all inline-block shadow-lg">
                Retour à l'accueil
            </a>
        </div>
    </div>
</main>
@endsection
