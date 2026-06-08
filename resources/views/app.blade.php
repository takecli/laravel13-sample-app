<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="session_domain" content="{{env('SESSION_DOMAIN')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    <style>
        html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        width: 100%;
        }
    </style>
    </head>
    <body id="top">
        <div id="app"></div>
    </body>
</html>