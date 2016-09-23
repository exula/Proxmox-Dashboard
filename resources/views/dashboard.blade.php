@extends('layout.main')

@section('content')
<h1>Dashboard</h1>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th align="right">CPU Load</th>
            <th align="right">Memory Usage</th>
            <th align="right">Started VMs</th>
        </tr>
    </thead>
@foreach($nodes as $node)
    <tr>
        <td><i class="fa fa-server"></i> {{ $node->name }}</td>
        <td align="right">{{ $node->load }}</td>
        <td align="right">{{ $node->memory }}</td>
        <td align="right">{{ $node->vmcount }}</td>
    </tr>
@endforeach
    <tr>
        <td colspan="3"></td>
        <td align="right"><i class="fa fa-tv"></i> <strong>{{ $totalvms }}</strong></td>
    </tr>
</table>


<h2>Recommendations</h2>
<p>
    @foreach($recommendations as $r)
        {{ $r }}
        <br/>
    @endforeach
</p>

{!! Form::open(array('route' => 'dorecommendations')) !!}
    <input type="hidden" name="recommendations" value="{{ json_encode($recommendations) }}">
    <button type="submit">Do Recommendations</button>
{!! Form::close() !!}

@stop