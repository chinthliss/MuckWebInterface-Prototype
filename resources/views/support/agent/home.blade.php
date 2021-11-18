@extends('layouts.layout')

@section('title')
    Support (Agent)
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Support (Agent)' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-list
        tickets-url="{{ $ticketsUrl }}"
        :category-configuration="{{ json_encode($categoryConfiguration) }}"
        :agent="true"
    ></support-ticket-list>
@endsection
