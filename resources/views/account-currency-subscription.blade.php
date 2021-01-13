@extends('layouts.layout')

@section('title')
    Account Currency Subscription
@endsection

@section('content')
    <account-currency-subscription
        :subscription="{{ json_encode($subscription) }}"
        :transactions="{{ json_encode($transactions) }}"
    ></account-currency-subscription>
@endsection
