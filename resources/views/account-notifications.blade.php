@extends('layouts.layout')

@section('title')
    Notifications
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'auth.account', 'label' => 'Account' ],
        [ 'route' => 'account.notifications', 'label' => 'Notifications' ]
    ]) }}
@endsection

@section('content')
    <account-notifications
        api-url="{{ $apiUrl }}"
    ></account-notifications>
@endsection
