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
        :initial-ticket="{{ json_encode($ticket) }}"
        :category-configuration="{{ json_encode($categoryConfiguration) }}"
        poll-url="{{ $pollUrl }}"
        update-url="{{ $updateUrl }}"
    ></support-ticket-agent>
@endsection
