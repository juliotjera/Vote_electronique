@props(['titre' => null])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1d4ed8">
<meta name="description" content="{{ __('Élections étudiantes en ligne : un électeur, un vote, secret et vérifiable.') }}">
<title>{{ $titre ? $titre.' · ' : '' }}{{ __('Vote électronique') }}</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
