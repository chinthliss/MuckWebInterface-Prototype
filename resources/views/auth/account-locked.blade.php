@extends('layouts.layout')

@section('title')
    Account Locked
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Account Locked' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
            <div class="row">
            <div class="col">
                This content is unavailable due to the presently logged in account being locked.
            </div>
        </div>
    </div>
@endsection
