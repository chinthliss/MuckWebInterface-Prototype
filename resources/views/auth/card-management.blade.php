@extends('layouts.layout')

@section('title')
    Card Management
@endsection

@section('content')
    {{ var_dump($response) }}
    <auth-card-management
        :response="{{ json_encode($response) }}"
    ></auth-card-management>
@endsection
