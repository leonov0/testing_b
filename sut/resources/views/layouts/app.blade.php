<!doctype html>
<html lang="{{ $lang ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Made in France — product catalogue')</title>
    @stack('head')
</head>
<body>
<header>
    <h1>Made in France — product catalogue</h1>
    <nav aria-label="Main">
        <a href="/products">Products</a>
        <a href="/companies">Companies</a>
        <a href="/companies/deactivated">Deactivated companies</a>
        <a href="/verify">GTIN verification</a>
    </nav>
</header>
<main>
    @yield('content')
</main>
</body>
</html>
