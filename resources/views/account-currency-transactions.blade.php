@extends('layouts.layout')

@section('title')
    Account Currency Transactions
@endsection

@section('content')
    <account-currency-transactions
        :transactions="{{json_encode($transactions)}}"
    ></account-currency-transactions>
@endsection
