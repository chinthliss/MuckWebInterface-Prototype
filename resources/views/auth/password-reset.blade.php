@extends('layouts.layout')

@section('title')
    Reset Password
@endsection

@section('content')
    <auth-password-reset
        :errors="{{ $errors }}"
    ></auth-password-reset>
@endsection
