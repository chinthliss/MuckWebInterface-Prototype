@extends('layouts.layout')

@section('title')
    Account Currency Transaction
@endsection

@section('content')
    <account-currency-transaction
        :transaction="{{ json_encode($transaction) }}"
        subscription-link=" {{ $transaction['subscription_id'] ? route('accountcurrency.subscription', ["id" => $transaction['subscription_id']]) : '' }}"
    ></account-currency-transaction>
@endsection
