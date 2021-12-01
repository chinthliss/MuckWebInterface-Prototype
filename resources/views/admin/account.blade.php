@extends('layouts.layout')

@section('title')
    Account {{ $account['id'] }} (Admin)
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.accounts', 'label' => 'Accounts' ],
        [ 'label' => 'Account ' . $account['id'] ],
    ]) }}
@endsection

@section('content')
    <admin-account
        :account="{{ json_encode($account) }}"
        muck-name="{{ $muckName }}"
        :previous-tickets = "{{ json_encode($previousTickets) }}"
    ></admin-account>
@endsection
