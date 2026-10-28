<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Members') | Membership Management</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=members-2">
</head>
<body class="membership-app">
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('members.index') }}">Membership Management</a>
            <nav aria-label="Main navigation">
                @auth
                    <a href="{{ route('members.index') }}" @if(request()->routeIs('members.*')) aria-current="page" @endif>Members</a>
                    <a href="{{ route('tasks.index') }}">Tasks exercise</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="text-button" type="submit">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('tasks.index') }}">Tasks exercise</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="page @yield('page-class')">
        @if(session('success'))
            <p class="notice" role="status">{{ session('success') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
