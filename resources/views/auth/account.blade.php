@extends('layouts.layout')

@section('title')
    Account
@endsection

@section('content')
    <auth-account
        email="{{ Auth::user()->getEmailForVerification() }}"
        account-created="{{ Auth::user()->createdAt ?? 'Prior to this being recorded' }}"
    ></auth-account>
@endsection
