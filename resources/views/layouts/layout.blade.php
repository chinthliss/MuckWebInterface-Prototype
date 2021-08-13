<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Account if logged in -->
    @auth
    <meta name="account-id" content="{{ Auth::user()->getAid() }}">
    @endauth

    <!-- Character if set -->
    @Character
    <meta name="character-dbref" content="{{ Auth::user()->getCharacterDbref() }}">
    @endCharacter

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

    <!-- FavIcons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/favicon32.png') }}">
</head>
<body>
<!-- Top header -->
<header id="site_navigation_top" class="navbar flex-column flex-md-row">
    <a class="navbar-brand mr-0 mr-md-2" href="{{ url('/') }}" aria-label="Site Logo">(LOGO) {{ Lex::get('app_name') }}</a>
    <div class="navbar-nav-scroll">
        <ul class="navbar-nav flex-row">
            @guest
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}">Login</a>
                </li>
            @else
                @Character
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">
                        {{ Auth::user()->getCharacterName() }}
                    </a>
                </li>
                @endCharacter
                @if (Route::has('auth.account'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('auth.account') }}">Account</a>
                    </li>
                @endif
                @if (Route::has('account.notifications'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('account.notifications') }}">
                            Notifications
                            <?php
                            /** @var App\User $user */
                            $user = auth()->user();
                            if ($user) {
                                $count = resolve('App\AccountNotificationManager')->getNotificationCountFor($user);
                                if ($count) echo('<span class="badge badge-light">' . $count . '</span>');
                            }
                            ?>
                        </a>
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
</header>
<!-- Breadcrumbs -->
<nav id="site_navigation_breadcrumbs" aria-label="Navigation Breadcrumbs">
    @yield('breadcrumbs')
</nav>
<!-- Button to open Navigation if on mobile -->
<div class="container-fluid">
    <button id="site_navigation_button" type="button" class="d-md-none btn btn-primary my-2">
        <i class="fas fa-bars btn-icon-left"></i>
        Navigation
    </button>
</div>
<div class="container-fluid">
    <div id="site_lower_container" class="row flex-xl-nowrap">
        <!-- Left side bar -->
        <nav id="site_navigation_left" class="col-12 col-md-3 col-xl-2">
            <div class="navbar-text">Some text!</div>

            @auth
                <h4 class="nav-link mt-2 pl-0"><a href="{{ route('singleplayer.home') }}">Singleplayer</a></h4>
                <div>???</div>

                <h4 class="nav-link mt-2 pl-0"><a href="{{ route('multiplayer.home') }}">Multiplayer</a></h4>
                <a class="nav-link" href="{{ route('multiplayer.gettingstarted') }}">Getting Started</a>
                <a class="nav-link" href="{{ route('multiplayer.character.select') }}">Character Select</a>
                <a class="nav-link" href="{{ route('multiplayer.connect') }}">Connect</a>
                <a class="nav-link" href="{{ route('multiplayer.avatar') }}">Avatar</a>
                <a class="nav-link" href="{{ route('accountcurrency') }}">Buy {{ Lex::get('accountcurrency') }}</a>

                @Staff
                <h4 class="nav-link mt-2 pl-0"><a href="{{ route('admin.home') }}">Game Staff</a></h4>
                <a class="nav-link" href="{{ route('admin.accounts') }}">Account Browser</a>
                @endStaff

                @Admin
                <h4 class="nav-link mt-2 pl-0"><a href="{{ route('admin.home') }}">Site Admin</a></h4>
                <a class="nav-link" href="{{ route('admin.logs') }}">Site Log Viewer</a>
                <a class="nav-link" href="{{ route('admin.patrons') }}">Patreon Supporter Browser</a>
                <a class="nav-link" href="{{ route('admin.subscriptions') }}">Payment Subscriptions</a>
                <a class="nav-link" href="{{ route('admin.transactions') }}">Payment Transactions</a>
                @endAdmin
            @endauth
        </nav>
        <!-- Right side bar -->
        <nav id="site_navigation_right" class="col-12 col-md-3 col-xl-2">
            <div class="navbar-text">Secondary Navigation Area</div>
            <div class="navbar-text">Located on the right on desktop, moves to middle on mobile and precedes page.</div>
            <div class="navbar-text">Intended for a page's individual navigation but can also host widgets (e.g. surveys)</div>
        </nav>
        <!-- Middle, main div that holds content -->
        <div id="site_content" class="col-12 col-md-6 col-xl-8">
            <!-- Javascript check -->
            <noscript>
                <div class="p-3 mb-2 bg-danger text-light rounded">This page requires javascript enabled in order to work.</div>
            </noscript>

            <!-- Site Notice -->
            @SiteNoticeExists
            <div class="p-3 mb-2 bg-warning text-dark rounded">
            <?php
                $filePath = public_path('site-notice.txt');
                echo(implode('<br/>', file($filePath, FILE_IGNORE_NEW_LINES)));
            ?>
            </div>
            @endSiteNoticeExists

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
            <main id="app" class="my-2 py-1">
                @yield('content')
            </main>
        </div>
    </div>
</div>
<script type="application/javascript">
    //Code for the navigation toggle when mobile
    $('#site_navigation_button').click(() => {
        $('#site_navigation_left').toggleClass('site_navigation_force_show');
    });

    //Lexicon for the game
    const lex_config = @json(Lex::toArray());
    const lex = (word) => {
        return lex_config[word] || word;
    };

    Vue.mixin({
        methods: {
            lex: (word) => {
                return lex(word);
            }
        }
    });

    //Attach Vue components - needs to be run after the page exists and DOM populated
    const app = new Vue({
        el: '#app',
    });

</script>
@yield('script')
</body>
</html>
