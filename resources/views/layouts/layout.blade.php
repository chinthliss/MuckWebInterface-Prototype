<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Account if logged in -->
    @auth
    <meta name="account-id" content="{{ Auth::user()->getAid() }}"
    @endauth

    <!-- Title -->
    @hasSection('title')
        <title>@yield('title') ({{ config('app.name', 'MuckWebInterface') }})</title>
    @else
        <title>{{ config('app.name', 'MuckWebInterface') }}</title>
@endif

<!-- Scripts -->
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/app.js') }}"></script>

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body>
<!-- Sidebar/Left pane -->
<nav id="leftpane" class="navbar-dark min-vh-100 p-2 shadow-sm">
    <a class="navbar-brand" href="{{ url('/') }}">
        {{ config('app.name', 'MuckWebInterface') }}
    </a>
    <div><span class="navbar-text">Some text!</span></div>

    @auth
        <div><a href="{{ route('accountcurrency') }}">Buy Account Currency</a></div>
    @endauth

    @Admin
    <div><a href="{{ route('admin.home') }}">Admin Dashboard</a></div>
    <div><a href="{{ route('admin.logs') }}">Log Viewer</a></div>
    <div><a href="{{ route('admin.subscriptions') }}">Subscriptions</a></div>
    @endAdmin
</nav>
<!-- Main/Right pane -->
<div id="mainpane">
    <!-- Topbar -->
    <nav class="navbar navbar-dark navbar-expand-md">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                (Brand)
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <div class="nav-link">Left Option</div>
                    </li>
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('auth.account') }}">
                                {{ Auth::user()->getCharacterName() }}
                            </a>
                        </li>
                        @if (Route::has('auth.account'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('auth.account') }}">Account</a>
                            </li>
                        @endif
                        @if (Route::has('logout'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Logout
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    @csrf
                                </form>
                            </li>
                        @endif
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
    @PrefersFullWidth
    <div class="container-fluid">
    @else
    <div class="container">
    @endPrefersFullWidth
        <div class="row">
            <div class="col">
                <div id="contentwrapper">
                    <!-- Javascript check -->
                    <noscript>
                        <div class="p-3 mb-2 bg-danger text-light rounded">This page requires javascript enabled in order to work.</div>
                    </noscript>
                    <!-- Site Notice -->
                    @SiteNotice
                    <div class="p-3 mb-2 bg-warning text-dark rounded">@SiteNoticeContent</div>
                    @endSiteNotice

                    <!-- Flashed Messages -->
                    @if ($message = Session::get('message-success'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>{{ $message }}</strong>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Content -->
                    <main id="app">
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript">
    //Attach Vue components - needs to be run after the page exists and DOM populated
    const app = new Vue({
        el: '#app',
    });
</script>
@yield('script')
</body>
</html>
