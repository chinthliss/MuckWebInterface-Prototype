@extends('layouts.layout')

@section('title')
    Card Management
@endsection

@section('content')
    <auth-card-management
        @isset($profile)
        :profile="{{ $profile }}"
        @endisset
    ></auth-card-management>
    <h2>Response</h2>
    {{ var_dump($response) }}
    <h2>Profile</h2>
    {{ var_dump($profile) }}

@endsection
