@extends('layouts.layout')

@section('title')
    Account Currency Transactions
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin', 'label' => 'Admin' ],
        [ 'route' => 'admin.transactions', 'label' => 'Transactions' ],
    ]) }}
@endsection

@section('content')
    <account-currency-transactions-admin></account-currency-transactions-admin>
@endsection
