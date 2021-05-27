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
        :character-slot-count="{{ $characterSlotCount }}"
        :character-slot-cost="{{ $characterSlotCost }}"

    ></character-select>
@endsection
