@extends('layouts.layout')

@section('content')
    <character-dashboard
        :characters="{{ $characters }}"
    ></character-dashboard>
@endsection
