<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>@yield('title', 'Dashboard') · GO Admin</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon-go.png') }}">

  <link href="{{ asset('admin/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="{{ asset('admin/css/sb-admin-2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('admin/css/style.css') }}" rel="stylesheet">
  <link href="{{ asset('admin/css/theme.css') }}?v={{ filemtime(public_path('admin/css/theme.css')) }}" rel="stylesheet">
  @stack('styles')
</head>
<body id="page-top">

@php
  $authUser = Auth::user();
  $initials = collect(explode(' ', trim($authUser->name ?? 'A')))->filter()->take(2)
      ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<div id="wrapper">
  @include('admin.layouts.sidebar')
  <div class="go-backdrop" data-go-nav-close></div>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">

      <header class="go-topbar">
        <button type="button" class="go-menu-btn" data-go-nav-toggle aria-label="{{ __('sidebar.open_menu') }}">
          <i class="fa fa-bars"></i>
        </button>

        <div class="go-crumb">
          {{ __('sidebar.admin_panel') }} &nbsp;/&nbsp; <strong>@yield('title', __('sidebar.dashboard'))</strong>
        </div>

        <div class="go-topbar-right">
          <span class="go-date"><i class="far fa-calendar"></i>{{ now()->translatedFormat('D, d M Y') }}</span>

          <div class="dropdown">
            <a href="#" class="go-user" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="go-avatar">{{ $initials }}</span>
              <span class="go-user-meta">
                <span class="go-user-name d-block">{{ $authUser->name }}</span>
                <span class="go-user-role d-block text-capitalize">{{ $authUser->role }}</span>
              </span>
              <i class="fas fa-chevron-down text-muted mr-1" style="font-size:10px"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <div class="dropdown-header">{{ $authUser->email }}</div>
              <a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-cog fa-fw mr-2 text-muted"></i>{{ __('sidebar.settings') }}</a>
              <div class="dropdown-divider"></div>
              <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
              <a href="#" class="dropdown-item text-danger"
                 onclick="event.preventDefault(); if (confirm({{ \Illuminate\Support\Js::from(__('sidebar.logout_confirm')) }})) { document.getElementById('logout-form').submit(); }">
                <i class="fas fa-sign-out-alt fa-fw mr-2"></i>{{ __('sidebar.logout') }}
              </a>
            </div>
          </div>
        </div>
      </header>
