@extends('layouts.layout')

@section('title')
    Account Currency
@endsection

@section('content')
    <account-currency-transaction
        :transaction="{{ json_encode($transaction) }}"
    ></account-currency-transaction>
@endsection
