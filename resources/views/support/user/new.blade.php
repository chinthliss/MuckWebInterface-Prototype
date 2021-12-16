@extends('layouts.layout')

@section('title') Raise a ticket @endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'support.user.home', 'label' => 'Support' ],
        [ 'label' => 'New Ticket' ]
    ]) }}
@endsection

@section('content')
    <support-ticket-new
        :category-configuration="{{ json_encode($categoryConfiguration) }}"
        :characters="{{ json_encode($characters) }}"
        :errors="{{ $errors }}"
        :old="{{ json_encode(old(), JSON_FORCE_OBJECT) }}"
    ></support-ticket-new>
@endsection
