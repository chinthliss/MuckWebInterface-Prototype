@extends('layouts.layout')

@section('title')Ticket {{ $ticket['id'] }}@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'support.agent.tickets', 'label' => 'Tickets (Agent)' ],
        [ 'label' => 'Ticket (Agent)' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-agent
        :initial-ticket="{{ json_encode($ticket) }}"
        :category-configuration="{{ json_encode($categoryConfiguration) }}"
        user-url="{{ $userUrl }}"
        poll-url="{{ $pollUrl }}"
        update-url="{{ $updateUrl }}"
        staff-character="{{ $staffCharacter }}"
    ></support-ticket-agent>
@endsection
