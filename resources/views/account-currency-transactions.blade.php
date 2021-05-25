@extends('layouts.layout')

@section('title')
    Account Currency Transactions
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'accountcurrency', 'label' => Lex::get('accountcurrency') ],
        [ 'route' => 'accountcurrency.transactions', 'label' => Lex::get('accountcurrency') . ' Transactions' ]
    ]) }}
@endsection

@section('content')
    <account-currency-transactions
        :transactions="{{json_encode($transactions)}}"
    ></account-currency-transactions>
@endsection
