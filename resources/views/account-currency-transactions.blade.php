@extends('layouts.layout')

@section('title')
    Account Currency Transactions
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'accountcurrency', 'label' => 'Account Currency' ],
        [ 'route' => 'accountcurrency.transactions', 'label' => 'Account Currency Transactions' ]
    ]) }}
@endsection

@section('content')
    <account-currency-transactions
        :transactions="{{json_encode($transactions)}}"
    ></account-currency-transactions>
@endsection
