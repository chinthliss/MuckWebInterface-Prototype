@extends('layouts.layout')

@section('title')
    Account View
@endsection

@section('content')
    <admin-account
        :account="{{ json_encode($account) }}"
        muck-name="{{ $muckName }}"
    ></admin-account>
@endsection
