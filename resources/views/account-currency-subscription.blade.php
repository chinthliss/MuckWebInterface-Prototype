@extends('layouts.layout')

@section('title')
    Account Currency Subscription
@endsection

@section('content')
    <account-currency-subscription
        :subscription="{{ json_encode($subscription) }}"
    ></account-currency-subscription>
@endsection
