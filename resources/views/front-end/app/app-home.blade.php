<!DOCTYPE html>
<html dir="ltr" lang="en">

<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="assets/images/favicon/faviconn.ico">
    <meta charset="UTF-8" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="https://bankhub-html.vercel.app/images/favicon.ico" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com/" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link rel="stylesheet" href="{{ asset('css/nice-select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('js/jsvectormap/dist/css/jsvectormap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('js/simple-datatables@9.0.0/dist/style.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/js-datepicker@5.18.2/dist/datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/swiper@11/swiper-bundle.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/1.3.6/quill.snow.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" />
    <link rel="stylesheet" href="{{ asset('css/line-awesome/css/line-awesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <title>{{ isset($title) ? $title . ' | ' . config('app.name') : config('app.name') }}</title>

<body @unless (empty($body_class)) class="{{ $body_class }}" @endunless>
    <!-- Loader -->
    <div
        class="loader flex items-center justify-center min-w-screen min-h-screen fixed !z-50 inset-0 bg-n0 dark:bg-bg4">
        <svg viewBox="25 25 50 50">
            <circle r="20" cy="50" cx="50"></circle>
        </svg>
    </div>


    @include('front-end/partials/header')

    @yield('content')

    @include('front-end/partials/footer')

    @yield('script_front_end')

</body>

</html>
