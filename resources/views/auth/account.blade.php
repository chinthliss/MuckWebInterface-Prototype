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
    ></auth-account>
@endsection
