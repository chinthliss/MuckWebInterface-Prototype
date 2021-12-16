@extends('layouts.layout')

@section('title') Raise a ticket @endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Staff' ],
        [ 'route' => 'support.agent.home', 'label' => 'Support' ],
        [ 'label' => 'New Ticket' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-new
        :category-configuration="{{ json_encode($categoryConfiguration) }}"
        :staff="true"
        :errors="{{ $errors }}"
        :old="{{ json_encode(old(), JSON_FORCE_OBJECT) }}"
    ></support-ticket-new>

@endsection
