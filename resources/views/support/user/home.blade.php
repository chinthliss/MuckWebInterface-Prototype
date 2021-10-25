@extends('layouts.layout')

@section('title')
    Support
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Support' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-list
        tickets-url="{{ $ticketsUrl }}"
    ></support-ticket-list>
@endsection
