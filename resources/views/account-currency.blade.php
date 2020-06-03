@extends('layouts.layout')

@section('title')
    Account Currency
@endsection

@section('content')
    <account-buy-currency
        default-card-masked-number = "{{ $defaultCardMaskedNumber }}"
        account = "{{ $account }}"
        :suggested-amounts = "{{ json_encode($suggestedAmounts) }}"
        account-currency-image="{{ asset('image/accountcurrency.png') }}"
        card-management-page = "{{ route('payment.cardmanagement') }}"
    ></account-buy-currency>
    <game-stretch-goals></game-stretch-goals>
@endsection
