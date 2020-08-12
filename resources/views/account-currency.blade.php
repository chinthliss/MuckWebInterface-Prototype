@extends('layouts.layout')

@section('title')
    Account Currency
@endsection

@section('content')
    <account-currency-buy
        default-card-masked-number="{{ $defaultCardMaskedNumber }}"
        account="{{ $account }}"
        :suggested-amounts="{{ json_encode($suggestedAmounts) }}"
        account-currency-image="{{ asset('image/accountcurrency.png') }}"
        card-management-page="{{ route('payment.cardmanagement') }}"
        :item-catalogue="{{ json_encode($itemCatalogue) }}"
    ></account-currency-buy>
    <game-stretch-goals></game-stretch-goals>
@endsection
