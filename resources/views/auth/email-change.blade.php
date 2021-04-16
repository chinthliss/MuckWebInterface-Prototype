@extends('layouts.layout')

@section('title')
    Change Email
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'auth.account', 'label' => 'Account' ],
        [ 'route' => 'auth.account.emailchange', 'label' => 'Change Email' ]
    ]) }}
@endsection

@section('content')
    <auth-email-change
        :errors="{{ $errors }}"
    ></auth-email-change>
@endsection
