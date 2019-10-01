<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @hasSection('title')
        <title>@yield('title') ({{ config('app.name', 'Laravel') }})</title>
    @else
        <title>{{ config('app.name', 'Laravel') }}</title>
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
<nav id="leftpane" class="navbar-dark bg-dark min-vh-100 p-2 shadow-sm">
    <a class="navbar-brand" href="{{ url('/') }}">
        {{ config('app.name', 'Laravel') }}
    </a>
    <div><span class="navbar-text">Some text!</span></div>
    <div><a href="#">A link</a></div>
</nav>
<!-- Main/Right pane -->
<div id="mainpane">
    <!-- Topbar -->
    <nav class="col navbar navbar-dark bg-dark navbar-expand-md">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                (Left text)
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

                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @else
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
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('auth.account') }}">Account</a>
                        </li>

                        <li class="nav-item">
                            {{ Auth::user()->playerName() }}
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="row">
            <div class="col">

                <div id="contentwrapper">
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
</body>
</html>
