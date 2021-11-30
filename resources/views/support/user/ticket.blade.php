@extends('layouts.layout')

@section('title')Ticket {{ $ticket['id'] }}@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'support.user.tickets', 'label' => 'Tickets' ],
        [ 'label' => 'Ticket' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-user
        :initial-ticket="{{ json_encode($ticket) }}"
        poll-url="{{ $pollUrl }}"
        update-url="{{ $updateUrl }}"
    ></support-ticket-user>
@endsection
