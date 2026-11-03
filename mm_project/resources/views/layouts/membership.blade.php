<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Members') | Membership Management</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo-symbol.png') }}">
    <script src="{{ asset('js/theme.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=members-17">
</head>
<body class="membership-app {{ request()->routeIs('login', 'login.store') ? '' : 'workspace-page' }}">
    <svg class="brand-filters" width="0" height="0" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <filter id="brand-dark-ink" color-interpolation-filters="sRGB">
                <feColorMatrix type="matrix" values="0.073 0 0 0 0.891 -0.889 1 0 0 0.854 -0.913 0 1 0 0.878 0 0 0 1 0"/>
            </filter>
        </defs>
    </svg>
    <a class="skip-link" href="#main-content">Skip to content</a>
    @unless(request()->routeIs('login', 'login.store'))
    <aside class="workspace-sidebar" aria-label="Membership workspace">
            <a class="brand" href="{{ route('members.index') }}" aria-label="Membership Management">
                <img class="brand-logo logo-light" src="{{ asset('images/brand/logo-horizontal-light.png') }}" alt="" width="216" height="72">
                <img class="brand-logo logo-dark" src="{{ asset('images/brand/logo-horizontal-light.png') }}" alt="" width="216" height="72">
            </a>
        <button class="workspace-menu-toggle" type="button" aria-expanded="true" aria-controls="workspace-navigation">Menu</button>
            <div class="workspace-menu-body" id="workspace-navigation">
            <nav aria-label="Main navigation">
                @auth
                    <a href="{{ route('members.index') }}" @if(request()->routeIs('members.*')) aria-current="page" @endif><span>Members</span><span class="nav-arrow" aria-hidden="true">&rarr;</span></a>
                @else
                    <a href="{{ route('login') }}">Staff Login</a>
                @endauth
            </nav>
            <div class="sidebar-footer">
            @include('partials.theme-toggle')
            @auth
                <div class="account-area" role="group" aria-label="Signed-in account">
                    <span class="staff-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="staff-identity"><span class="staff-name">{{ auth()->user()->name }}</span><span class="staff-role">Administrator</span></span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="text-button" type="submit">Sign out</button>
                    </form>
                </div>
            @endauth
            </div>
            </div>
    </aside>
    @endunless
    <main id="main-content" class="page @yield('page-class')" tabindex="-1">
        @if(session('success'))
            <p class="notice" role="status">{{ session('success') }}</p>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
    @unless(request()->routeIs('login', 'login.store'))
        <script src="{{ asset('js/workspace.js') }}?v=2" defer></script>
    @endunless
</body>
</html>
