@extends('layouts.layout')

@section('title')
    Patreon Browser
@endsection

@section('content')
    <admin-patreon-browser
        api-url="{{ $apiUrl }}"
    ></admin-patreon-browser>
@endsection
