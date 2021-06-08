@extends('layouts.layout')

@section('title')
    Admin Dashboard
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ]
    ]) }}
@endsection

@section('content')
    Admin Dashboard
    <br/>
    TBC. This will have more purpose as additional items are produced.
@endsection
