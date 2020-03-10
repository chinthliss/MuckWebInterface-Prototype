@extends('layouts.layout')

@section('title')
    Character Dashboard
@endsection

@section('content')
    <character-dashboard
        :characters="{{ $characters }}"
    ></character-dashboard>
@endsection
