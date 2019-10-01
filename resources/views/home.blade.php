@extends('layouts.layout')

@section('content')
<div class="row justify-content-center">
    <div class="col">
        <div class="card">
            <h4 class="card-header">Dashboard</h4>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                You are logged in!
            </div>
        </div>
    </div>
</div>
@endsection
