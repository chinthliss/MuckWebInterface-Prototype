@extends('layouts.layout')

@section('title')
    Accounts (Admin)
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Accounts' ],
    ]) }}
@endsection

@section('content')
    <admin-accounts
        api-url="{{ $apiUrl }}"
    >
    </admin-accounts>
@endsection
