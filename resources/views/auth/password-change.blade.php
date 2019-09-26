@extends('layouts.layout')

@section('title')
    Change Password
@endsection

@section('content')
    <auth-password-change
        :errors="{{ $errors }}"
    ></auth-password-change>
@endsection
