@extends('layouts.layout')

@section('title')
    Account Currency
@endsection

@section('content')
    <account-buy-currency-via-card
        default-card-masked-number = "{{ $defaultCardMaskedNumber }}"
        account = "{{ $account }}"
        card-management-page = "{{ route('payment.cardmanagement') }}"
        :suggested-amounts = "{{ json_encode($suggestedAmounts) }}"
    ></account-buy-currency-via-card>
    <game-stretch-goals></game-stretch-goals>
@endsection
