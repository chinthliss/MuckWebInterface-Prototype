@extends('layouts.layout')

@section('title')
    Avatar Doll Tester
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'admin.avatar.dolllist', 'label' => 'Avatar Doll List' ],
        [ 'label' => 'Avatar Doll Test' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-doll-tester
        :drawing-steps = "{{ json_encode($drawingSteps) }}"
        :dolls = "{{ json_encode($dolls) }}"
        :gradients = "{{ json_encode($gradients) }}"
        initial-code = "{{ $code }}"
        base-url = "{{ route('admin.avatar.dolltest') }}"
        render-url = "{{ route('admin.avatar.render') }}"
        :avatar-width = "{{ $avatarWidth }}"
        :avatar-height = "{{ $avatarHeight }}"
    >
    </admin-avatar-doll-tester>
@endsection
