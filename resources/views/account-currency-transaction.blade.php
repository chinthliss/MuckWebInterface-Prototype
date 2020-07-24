@extends('layouts.layout')

@section('title')
    Account Currency Transaction
@endsection

@section('content')
    <account-currency-transaction
        :transaction="{{ json_encode($transaction) }}"
    ></account-currency-transaction>
@endsection
