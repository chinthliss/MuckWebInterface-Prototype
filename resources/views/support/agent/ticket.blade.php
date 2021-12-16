@extends('layouts.layout')

@section('title') Ticket {{ $ticket['id'] }} @endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Staff' ],
        [ 'route' => 'support.agent.home', 'label' => 'Support' ],
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
