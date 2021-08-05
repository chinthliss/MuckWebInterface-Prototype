@extends('layouts.layout')

@section('title')
    Getting Started - Multiplayer
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Getting Started' ]
    ]) }}
@endsection

@section('content')
    <multiplayer-getting-started
        :account="{{ $hasAccount ? 'true' : 'false' }}"
        account-url="{{ route('multiplayer.home') }}"

        :character="{{ $hasAnyCharacter ? 'true' : 'false' }}"
        character-url="{{ route('multiplayer.character.create') }}"

        :character-active="{{ $hasActiveCharacter ? 'true' : 'false' }}"
        character-active-url="{{ route('multiplayer.character.select') }}"

        :character-approved="{{ $hasApprovedCharacter ? 'true' : 'false' }}"
        character-approved-url="{{ route('multiplayer.character.finalize') }}"

        direct-connect-url="{{ route('multiplayer.connect') }}"
        reset-character-password-url="{{ route('multiplayer.character.changepassword') }}"

        :page-recommendations="{{ json_encode($pageRecommendations) }}"
    >
    </multiplayer-getting-started>
@endsection
