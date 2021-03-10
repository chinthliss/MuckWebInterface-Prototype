@extends('layouts.layout')

@section('title')
    Account View
@endsection

@section('content')
    <admin-account
        :account="{{ json_encode($account) }}"
    ></admin-account>
@endsection
