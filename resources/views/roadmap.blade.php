@extends('layouts.layout')

@section('title')
    Site Roadmap
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'roadmap', 'label' => ' Site Roadmap' ]
    ]) }}
@endsection

@section('content')
    <roadmap
        :roadmap = "{{ json_encode($roadmap) }}"
        phase = "{{ $phase }}"
        phase-description = "{{ $phaseDescription }}"
        future = "{{ $future }}"
    ></roadmap>
@endsection
