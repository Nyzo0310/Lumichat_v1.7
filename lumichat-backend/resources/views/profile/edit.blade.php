@extends('layouts.app')

@section('title', 'Profile')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="py-20">
  <div class="max-w-4xl mx-auto px-4 space-y-10">

    {{-- Greeting --}}
    <div class="text-center space-y-1">
      <h2 class="title-dynamic text-3xl font-semibold">{{ $greeting }}, {{ Auth::user()->name }}</h2>
      <p class="muted-dynamic">Welcome back! You can manage your personal information and security settings below.</p>
    </div>

    {{-- Profile info + edit form --}}
    <div class="card-shell p-6">
      @include('profile.partials.update-profile-information-form', [
        'user' => $user,
        'registration' => $registration ?? null
      ])
    </div>

    {{-- Update Password --}}
    <div class="card-shell p-6">
      @include('profile.partials.update-password-form')
    </div>

    {{-- Delete Account --}}
    <div class="card-shell p-6">
      @include('profile.partials.delete-user-form')
    </div>
  </div>
</div>
@endsection
