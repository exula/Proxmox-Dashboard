@extends('layout.guest')

@section('content')

    <div class="row">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <div class="card panel-info">
                <div class="card-header">
                    <h1>Login</h1>
                    <small>Login using your ProxmoxVE credentials</small>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/login') }}">
                        {{ csrf_field() }}

                        <label for="name">Username</label>
                        <input type="text" name="username" class="form-control">
                        <br>
                        <label for="password">Password</label>
                        <input type="password" name="password" class="form-control">
                        <br/>
                        <label>Realm:</label>
                        <select name="realm" class="form-control">
                            @foreach($realms as $key => $value)
                                <option value='{{ $key }}'> {{ $value }}</option>
                            @endforeach
                        </select>
                        <br/>
                        <button type="submit" name="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4"></div>
    </div>


@endsection
