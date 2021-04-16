@extends('layouts.layout')

@section('title')
    Account View (Admin)
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'View Account' ],
    ]) }}
@endsection

@section('content')
    <admin-account
        :account="{{ json_encode($account) }}"
        muck-name="{{ $muckName }}"
    ></admin-account>
@endsection
