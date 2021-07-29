@extends('layouts.layout')

@section('title')
    Account
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'auth.account', 'label' => 'Account' ]
    ]) }}
@endsection

@section('content')
    <auth-account
        account-created="{{ $user->createdAt ?? 'Prior to this being recorded' }}"
        :emails="{{ json_encode($user->getEmails()) }}"
        :errors="{{ $errors }}"
        :subscriptions="{{ json_encode($subscriptions) }}"
        subscription-active = "{{ $subscriptionActive }}"
        subscription-renewing = "{{ $subscriptionRenewing }}"
        subscription-expires = "{{ $subscriptionExpires }}"
        :initial-use-full-width="{{ json_encode($user->getPrefersFullWidth()) }}"
        :initial-hide-avatars="{{ json_encode($user->getPrefersNoAvatars()) }}"
    ></auth-account>
@endsection
