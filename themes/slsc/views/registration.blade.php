@extends('theme-slsc::layout')

@section('title', __('theme-slsc::interface.regModalTitle'))

@section('content')
<div class="container" style="max-width: 900px; padding-top: 3rem; padding-bottom: 4rem;">
    <header class="registration-heading">
        <h1 id="registrationTitle" class="section-title">{{ __('theme-slsc::interface.regModalTitle') }}</h1>
        <p id="registrationIntro" class="section-subtitle">{{ __('theme-slsc::interface.regIntro') }}</p>
    </header>
    @include('theme-slsc::partials.registration-form')
</div>
@endsection
