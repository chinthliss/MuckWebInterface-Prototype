@extends('layouts.layout')

@section('title')
    Terms of Service
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col">
                <div class="card">
                    <h4 class="card-header">Terms of Service</h4>
                    <div class="card-body">
                        <div>
                            @foreach ($termsOfService as $line)
                                {{ $line }} <br/>
                            @endforeach
                        </div>
                        @auth
                            @if ($agreed)
                                <div class="p-2 mb-2 bg-primary text-dark">You've agreed to this previously.</div>
                            @else
                                You need to agree!
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
