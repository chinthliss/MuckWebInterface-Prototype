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
        initial-avatar-preference = "{{ $avatarPreference }}"
        avatar-preference-url = "{{ route('account.avatar.preference') }}"
    ></auth-account>
@endsection
