@extends('layout.main')

@section('content')
    <h2>Failure Domains</h2>
    <table class="table table-bordered">
        <tr>
            <th>Node Name</th>
            <th>
                Failure Domain
            </th>
        </tr>
    @foreach($current as $node)
        <tr>
            <td>
                {{ $node['name'] }}
            </td>
            <td>
                {{ $node['domain'] }}
            </td>
        </tr>
    @endforeach
    </table>

    <h2>Recommendations</h2>
    <p>
        In order to bring the cluster into a safe state I have the following migration recommendations.
    </p>

    <ul>
    @foreach($recommendations as $rec)
        <li>{{ $rec }} </li>
    @endforeach
    </ul>
@stop