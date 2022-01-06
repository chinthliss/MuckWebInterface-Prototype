@extends('layouts.layout')

@section('title')
    Avatar Doll List
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar Doll List' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-doll-list
        :dolls = "{{ json_encode($dolls) }}"
        :invalid = "{{ json_encode($invalid, JSON_FORCE_OBJECT) }}"
    >
    </admin-avatar-doll-list>
@endsection
