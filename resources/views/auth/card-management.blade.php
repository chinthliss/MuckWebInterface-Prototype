@extends('layouts.layout')

@section('title')
    Card Management
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'auth.account', 'label' => 'Account' ],
        [ 'route' => 'payment.cardmanagement', 'label' => 'Card Management' ]
    ]) }}
@endsection

@section('content')
    <account-card-management
        @isset($profileId)
        profile-id="{{ $profileId }}"
        @endisset
        @isset($cards)
        :initial-cards="{{ json_encode($cards) }}"
        @endisset
    ></account-card-management>
    <div style="float:right">
        <!-- (c) 2005, 2011. Authorize.Net is a registered trademark of CyberSource Corporation -->
        <div class="AuthorizeNetSeal">
            <script type="text/javascript">
                var ANS_customer_id="{{ $sealId }}";
            </script>
            <script type="text/javascript" src="//verify.authorize.net/anetseal/seal.js"></script>
            <a href="http://www.authorize.net/" id="AuthorizeNetText" target="_blank">Credit Card Merchant Services</a>
        </div>
    </div>
@endsection
