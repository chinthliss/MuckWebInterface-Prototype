@extends('layouts.layout')

@section('title')
    Account Role List
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.logs', 'label' => 'Account Role List' ],
    ]) }}
@endsection

@section('content')
    <account-roles
        :users = "{{ json_encode($users) }}"
    ></account-roles>
@endsection
