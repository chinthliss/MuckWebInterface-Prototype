@extends('layouts.layout')

@section('title')
    Avatar
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col text-center">
                <avatar-edit
                    :present-customizations = "{{ json_encode($presentCustomizations) }}"
                    :gradients="{{ json_encode($gradients) }}"
                    render-url = "{{ route('multiplayer.avatar.edit.render') }}"
                    :avatar-width = "{{ $avatarWidth }}"
                    :avatar-height = "{{ $avatarHeight }}"
                >
                </avatar-edit>

            </div>
        </div>
    </div>
@endsection
