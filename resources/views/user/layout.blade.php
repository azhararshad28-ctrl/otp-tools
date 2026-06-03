<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Premium OTP</title>
    <link rel="stylesheet" href="/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-bolt"></i>
                <span>OTP Hub</span>
            </div>
            
            <nav class="nav-menu">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie nav-icon"></i> Overview
                </a>
                <a href="{{ route('generate.page') }}" class="nav-item {{ request()->routeIs('generate.page') ? 'active' : '' }}">
                    <i class="fa-solid fa-plus-circle nav-icon"></i> New Number
                </a>
                <a href="{{ route('numbers.page') }}" class="nav-item {{ request()->routeIs('numbers.page') ? 'active' : '' }}">
                    <i class="fa-solid fa-mobile-screen nav-icon"></i> My Numbers
                </a>
                <a href="#" class="nav-item" onclick="alert('Billing feature coming soon!')">
                    <i class="fa-solid fa-wallet nav-icon"></i> Top Up Balance
                </a>
                <a href="#" class="nav-item" onclick="alert('API Keys coming soon!')">
                    <i class="fa-solid fa-code nav-icon"></i> Developer API
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-item" style="width:100%; background:transparent; border:none; cursor:pointer; text-align:left;">
                        <i class="fa-solid fa-arrow-right-from-bracket nav-icon" style="color:var(--danger)"></i> 
                        <span style="color:var(--danger)">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <main class="main-wrapper">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <h1>@yield('header', 'Dashboard')</h1>
                </div>
                <div class="topbar-right">
                    <div class="wallet-badge">
                        <i class="fa-solid fa-coins"></i> $124.50
                    </div>
                    <div class="user-profile">
                        <div class="avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content-area">
                @if(session('success'))
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>
