@extends('layouts.layout')

@section('title')
    Raise a ticket
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'support.user.tickets', 'label' => 'Tickets' ],
        [ 'label' => 'Ticket' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-new
        :category-configuration="{{ json_encode($categoryConfiguration) }}"
        :errors="{{ $errors }}"
        :old="{{ json_encode(old(), JSON_FORCE_OBJECT) }}"
    ></support-ticket-new>
@endsection
