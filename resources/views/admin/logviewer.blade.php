@extends('layouts.layout')

@section('title')
    Log Viewer
@endsection

@section('content')
    <admin-log-viewer
        :dates="{{ json_encode($dates) }}"
    ></admin-log-viewer>
@endsection
