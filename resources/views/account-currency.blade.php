@extends('layouts.layout')

@section('title')
    Account Currency
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'accountcurrency', 'label' => 'Account Currency' ]
    ]) }}
@endsection

@section('content')
    <account-currency-buy
        default-card-masked-number="{{ $defaultCardMaskedNumber }}"
        default-card-expiry-date="{{ $defaultCardExpiryDate }}"
        account="{{ $account }}"
        :suggested-amounts="{{ json_encode($suggestedAmounts) }}"
        account-currency-image="{{ asset('image/accountcurrency.png') }}"
        card-management-page="{{ route('payment.cardmanagement') }}"
        :item-catalogue="{{ json_encode($itemCatalogue) }}"
        first-donation="{{ $firstDonation }}"
        last-donation-time="{{ $lastDonation }}"
        currency-discount-time="{{ $currencyDiscountTime }}"
        currency-discount="{{ $currencyDiscount }}"
    ></account-currency-buy>
    <game-stretch-goals
        :progress="{{ $stretchGoals['progress'] }}"
        :goals="{{ json_encode($stretchGoals['goals']) }}"
    ></game-stretch-goals>
@endsection
