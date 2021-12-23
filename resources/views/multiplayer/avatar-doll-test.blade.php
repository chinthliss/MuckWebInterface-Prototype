@extends('layouts.layout')

@section('title')
    Avatar Doll Test
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar Doll Test' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-doll-tester
        :dolls = "{{ json_encode($dolls) }}"
    >
    </admin-avatar-doll-tester>
@endsection
