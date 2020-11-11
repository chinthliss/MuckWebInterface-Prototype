@extends('layouts.layout')

@section('title')
    Account
@endsection

@section('content')
    <auth-account
        account-created="{{ $user->createdAt ?? 'Prior to this being recorded' }}"
        primary-email="{{ $user->getEmailForVerification() }}"
        :emails="{{ $user->getEmails() }}"
        :errors="{{ $errors }}"
        :subscriptions="{{ json_encode($subscriptions) }}"
        :initial-use-full-width="{{ json_encode($user->getPrefersFullWidth()) }}"
        :initial-hide-avatars="{{ json_encode($user->getPrefersNoAvatars()) }}"
    ></auth-account>
@endsection
