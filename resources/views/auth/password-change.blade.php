@extends('layouts.layout')

@section('title')
    Change Password
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'auth.account', 'label' => 'Account' ],
        [ 'route' => 'auth.account.passwordchange', 'label' => 'Change Password' ]
    ]) }}
@endsection

@section('content')
    <auth-password-change
        :errors="{{ $errors }}"
    ></auth-password-change>
@endsection
