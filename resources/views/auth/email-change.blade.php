@extends('layouts.layout')

@section('title')
    Change Email
@endsection

@section('content')
    <auth-email-change
        :errors="{{ $errors }}"
    ></auth-email-change>
@endsection
