@extends('layouts.layout')

@section('title')
    Reset Password Request
@endsection

@section('content')
    <auth-password-forgotten
        :errors="{{ $errors }}"
    ></auth-password-forgotten>
@endsection
