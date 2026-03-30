@extends('front-end/app/app-home', ['title' => 'Transaction enregistrée avec succès', 'body_class' => 'vertical bg-secondary1/5 dark:bg-bg3 success-page'])



@section('content')
    <main class="main-content has-sidebar">
        <div class="card">
            <div style="border-radius:200px; height:200px; width:200px; background: #88B04B; margin:0 auto;">
                <i class="checkmark">✓</i>
            </div>
            <h1>Succès</h1>
            <p>Votre demande a été enregistrée avec succès<br /> Nous reviendrons vers vous dans les plus brefs délais</p>

            <button class="btn ac-modal-btn">
                <a href="{{ route('dashboard') }}">Retourner vers le tableau de bord</a>

            </button>
        </div>
    </main>
@endsection
