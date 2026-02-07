<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0b0b1c">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>EchoBuzzer</title>
    <script>
        window.__REVERB_HOST__ = @json(env('VITE_REVERB_HOST', ''));
        window.__REVERB_PORT__ = @json((int) env('REVERB_HOST_PORT', env('REVERB_PORT', 8080)));
        window.__REVERB_APP_KEY__ = @json(env('REVERB_APP_KEY', 'local'));
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
