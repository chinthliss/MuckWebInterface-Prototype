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
    <h2>Profile Debug - {{ $profile }} </h2>
@endsection
