@extends('layouts.layout')

@section('title')
    Account Currency Transactions
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.transactions', 'label' => Lex::get('accountcurrency') . 'Transactions' ],
    ]) }}
@endsection

@section('content')
    <account-currency-transactions-admin></account-currency-transactions-admin>
@endsection
