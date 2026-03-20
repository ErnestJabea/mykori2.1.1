@extends('front-end/app/app', ['Login', 'body_class' => 'inner-page apropos-page'])

@section('content')

    <body class="">
        <section class="kori-login-container">
            <section class="kori-login-window">
                <div class="kori-login-left">
                    <div class="kori-logo">
                        <a href="#"><img src="{{ asset('assets/images/kori/kori-logo.png') }}"></a>
                    </div>
                    <p>
                        Suivez la performance de vos placements en temps réel grâce&nbsp;à votre plateforme <b>myKORI</b>
                    </p>
                </div>
                <div class="kori-login-right">
                    <h2>Code d'authenfication</h2>
                    @if (!$errors->isEmpty())
                        <div class="alert alert-red">
                            <ul class="list-unstyled">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form class="kori-login-form" name="f" action="{{ route('code-verification') }}" method="POST">
                        {{ csrf_field() }}
                        <p>Un code OTP a été envoyé à l'adresse <strong>{{ auth()->user()->email }}</strong></p>
                        <input type="text" name="codeopt" id="codeopt" required placeholder="Code d'authenfication">
                        <button type="submit" class="login-button">
                            Se connecter
                        </button>
                    </form>
                </div>
            </section>
        </section><!--jQuery-->

        <script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
        <!--Bootstrap-->
        <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
        <!-- Waypoint js -->
        <script src="{{ asset('assets/js/jquery.waypoints.js') }}"></script>
        <!-- Counter Up -->
        <script src="{{ asset('assets/js/jquery.counterup.min.js') }}"></script>
        <!--Owl-Carousel-->
        <script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
        <!--Parallax-->
        <script src="{{ asset('assets/js/parallax.min.js') }}"></script>
        <!-- Isotope JS -->
        <script src="{{ asset('assets/js/isotope.pkgd.min.js') }}"></script>
        <!-- MMenu JS -->
        <script src="{{ asset('assets/js/jquery.slicknav.min.js') }}"></script>
        <!-- magnific Js -->
        <script src="{{ asset('assets/js/jquery.magnific-popup.min.js') }}"></script>
        <!-- Progress Bar -->
        <script src="{{ asset('assets/js/jquery.lineProgressbar.js') }}"></script>
        <!-- Countdown Js -->
        <script src="{{ asset('assets/js/jquery.countdown.min.js') }}"></script>
        <!--Custom Js-->
        <script src="{{ asset('assets/js/custom.js') }}"></script>
    </body>
@endsection
