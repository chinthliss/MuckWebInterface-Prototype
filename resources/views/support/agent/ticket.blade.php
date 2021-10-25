@extends('layouts.layout')

@section('title')
    Ticket {{ $ticket['id'] }}
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'support.agent.tickets', 'label' => 'Tickets (Agent)' ],
        [ 'label' => 'Ticket' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-agent
        :ticket="{{ json_encode($ticket) }}"
    ></support-ticket-agent>
@endsection
