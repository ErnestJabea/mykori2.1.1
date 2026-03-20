<!DOCTYPE html>
<html dir="ltr" lang="en">

<meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="assets/images/favicon/faviconn.ico">
    <link rel="stylesheet" href="css/main.css" type="text/css}" />

    <!--Bootstrap-->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <!--Fontawesome-->
    <link rel="stylesheet" type="text/css" href="assets/css/all.min.css">
    <!--Owl-carousel-->
    <link rel="stylesheet" type="text/css" href="assets/css/owl.carousel.min.css">
    <!--Owl Carousel theme-->
    <link rel="stylesheet" type="text/css" href="assets/css/owl.theme.default.min.css">
    <!--Animate-->
    <link rel="stylesheet" type="text/css" href="assets/css/animate.min.css">
    <!-- magnific CSS -->
    <link rel="stylesheet" type="text/css" href="assets/css/magnific-popup.css">
    <!-- Mmenu CSS -->
    <link rel="stylesheet" type="text/css" href="assets/css/slicknav.min.css">
    <!--Style-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <!-- Responsive -->
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
    <!-- Material Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <title>MY KORI - Home</title>
</head>

<body @unless (empty($body_class))
    class="{{ $body_class }}"
    @endunless>
    <!--page start-->

    <div class="container-banner">

        @yield('content')


        @yield('script_front_end')

</body>

</html>
