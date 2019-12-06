@extends('layouts.layout')

@section('title')
    Card Management
@endsection

@section('content')
    <auth-card-management
        @isset($profileId)
        profile-id="{{ $profileId }}"
        @endisset
        @isset($cards)
        :initial-cards="{{ json_encode($cards) }}"
        @endisset
    ></auth-card-management>
    <h2>Profile Debug - {{ $profileId }} </h2>
@endsection
