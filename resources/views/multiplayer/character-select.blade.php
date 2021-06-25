@extends('layouts.layout')

@section('title')
    Character Select
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Character Select' ]
    ]) }}
@endsection

@section('content')
    <character-select
        :characters="{{ json_encode($characters) }}"
        buy-character-slot-url="{{ route('multiplayer.character.buySlot') }}"
        create-character-url="{{ route('multiplayer.character.create') }}"
        :initial-character-slot-count="{{ $characterSlotCount }}"
        :initial-character-slot-cost="{{ $characterSlotCost }}"
    ></character-select>
@endsection
