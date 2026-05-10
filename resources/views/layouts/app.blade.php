<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Northstar Homeschool') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <tallstackui:script />
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $currentUser = auth()->user();
        $currentRole = $currentUser->role?->label() ?? 'User';
    @endphp

    <body class="font-sans antialiased"
          x-cloak
          x-data="{ name: @js($currentUser->name), menuOpen: false }"
          x-on:name-updated.window="name = $event.detail.name">
        <x-dialog />
        <x-toast />

        <div class="app-shell">
            <aside class="desktop-rail" aria-label="Primary navigation">
                <a href="{{ route('dashboard') }}" class="brand-mark" aria-label="Northstar Homeschool">
                    <span>N</span>
                </a>

                <nav class="rail-nav">
                    <a href="{{ route('dashboard') }}" @class(['rail-link' => true, 'active' => request()->routeIs('dashboard')]) title="Assignments" aria-label="Assignments">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Zm9 0A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4ZM4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Zm9 0a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                        </svg>
                    </a>

                    @can('create', \App\Models\Student::class)
                        <a href="{{ route('students.index') }}" @class(['rail-link' => true, 'active' => request()->routeIs('students.*')]) title="Students" aria-label="Students">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8.75 11.5a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Zm0-1.5a2.25 2.25 0 1 1 0-4.5 2.25 2.25 0 0 1 0 4.5Zm6.5 1.25a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Zm0-1.5a1.75 1.75 0 1 1 0-3.5 1.75 1.75 0 0 1 0 3.5ZM2.75 20a.75.75 0 0 1-.75-.75C2 15.9 4.7 14 8.75 14s6.75 1.9 6.75 5.25a.75.75 0 0 1-.75.75h-12Zm.82-1.5h10.36c-.42-1.82-2.3-3-5.18-3s-4.76 1.18-5.18 3Zm12.7 1.5a.75.75 0 0 1 0-1.5h4.16c-.35-1.4-1.72-2.25-3.93-2.25a.75.75 0 0 1 0-1.5c3.37 0 5.5 1.75 5.5 4.5a.75.75 0 0 1-.75.75h-4.98Z" />
                            </svg>
                        </a>
                    @endcan

                    <a href="{{ route('courses.index') }}" @class(['rail-link' => true, 'active' => request()->routeIs('courses.*')]) title="Courses" aria-label="Courses">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5.75 4A2.75 2.75 0 0 0 3 6.75v10.5A2.75 2.75 0 0 0 5.75 20h12.5A2.75 2.75 0 0 0 21 17.25V6.75A2.75 2.75 0 0 0 18.25 4H5.75Zm0 1.5h5.5v13h-5.5a1.25 1.25 0 0 1-1.25-1.25V6.75A1.25 1.25 0 0 1 5.75 5.5Zm7 13v-13h5.5a1.25 1.25 0 0 1 1.25 1.25v10.5a1.25 1.25 0 0 1-1.25 1.25h-5.5Z" />
                        </svg>
                    </a>

                    @can('viewAny', \App\Models\User::class)
                        <a href="{{ route('users.index') }}" @class(['rail-link' => true, 'active' => request()->routeIs('users.*')]) title="Users" aria-label="Users">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12.5a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Zm0-1.5a2.75 2.75 0 1 1 0-5.5 2.75 2.75 0 0 1 0 5.5Zm-7.25 9a.75.75 0 0 1-.75-.75C4 15.8 7.26 14 12 14s8 1.8 8 5.25a.75.75 0 0 1-.75.75H4.75Zm.83-1.5h12.84c-.5-1.84-2.77-3-6.42-3s-5.92 1.16-6.42 3Z" />
                            </svg>
                        </a>
                    @endcan

                    <a href="{{ route('user.profile') }}" @class(['rail-link' => true, 'active' => request()->routeIs('user.profile')]) title="Profile" aria-label="Profile">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 12.5a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Zm0-1.5a2.75 2.75 0 1 1 0-5.5 2.75 2.75 0 0 1 0 5.5Zm-7.25 9a.75.75 0 0 1-.75-.75C4 15.8 7.26 14 12 14s8 1.8 8 5.25a.75.75 0 0 1-.75.75H4.75Zm.83-1.5h12.84c-.5-1.84-2.77-3-6.42-3s-5.92 1.16-6.42 3Z" />
                        </svg>
                    </a>
                </nav>

                <div class="rail-bottom">
                    <a href="{{ route('user.profile') }}" class="rail-user-card" title="{{ $currentUser->name }} — {{ $currentRole }}" aria-label="Signed in as {{ $currentUser->name }}, {{ $currentRole }}">
                        <div class="rail-avatar" x-text="name ? name.charAt(0).toUpperCase() : 'P'"></div>
                        <span>{{ $currentRole }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rail-link" type="submit" title="Logout" aria-label="Logout">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5.75 4A2.75 2.75 0 0 0 3 6.75v10.5A2.75 2.75 0 0 0 5.75 20h5.5a.75.75 0 0 0 0-1.5h-5.5a1.25 1.25 0 0 1-1.25-1.25V6.75A1.25 1.25 0 0 1 5.75 5.5h5.5a.75.75 0 0 0 0-1.5h-5.5Zm9.22 4.47a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9.75a.75.75 0 0 1 0-1.5h6.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </aside>

            <header class="mobile-top-nav">
                <a href="{{ route('dashboard') }}" class="brand-mark" aria-label="Northstar Homeschool">
                    <span>N</span>
                    <strong>Northstar</strong>
                    <small>{{ $currentRole }}</small>
                </a>

                <button class="mobile-menu-button" type="button" x-on:click="menuOpen = ! menuOpen" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <nav class="mobile-menu-panel" x-bind:class="{ 'is-open': menuOpen }">
                    <div class="mobile-user-strip">
                        <strong>{{ $currentUser->name }}</strong>
                        <span>{{ $currentRole }}</span>
                    </div>
                    <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Assignments</a>
                    @can('create', \App\Models\Student::class)
                        <a href="{{ route('students.index') }}" @class(['active' => request()->routeIs('students.*')])>Students</a>
                    @endcan
                    <a href="{{ route('courses.index') }}" @class(['active' => request()->routeIs('courses.*')])>Courses</a>
                    @can('viewAny', \App\Models\User::class)
                        <a href="{{ route('users.index') }}" @class(['active' => request()->routeIs('users.*')])>Users</a>
                    @endcan
                    <a href="{{ route('user.profile') }}" @class(['active' => request()->routeIs('user.profile')])>Profile</a>
                    <form method="POST" action="{{ route('logout') }}" class="mobile-logout">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                </nav>
            </header>

            <main class="app-main">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
