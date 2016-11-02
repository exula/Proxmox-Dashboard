@extends('layout.main')

@section('content')
<h1>Dashboard</h1>

<div class="panel panel-default">
    <div class="panel-heading">
        Cluster Resources
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-md-3 text-center">
                <h2>CPU</h2>

                <div class="chart" id="cpuchart" data-percent="{{ round($status['cpu']['used']*100) }}">
                    <span class="percent">{{ round($status['cpu']['used']*100) }}
                    </span>
                </div>
                <div>
                    {{ round($status['cpu']['used']*100) }}% of {{ $status['cpu']['total'] }} CPU(s)
                </div>

            </div>
            <div class="col-md-3 text-center">
                <h2>Memory</h2>

                <div class="chart" id="memorychart" data-percent="{{ round(($status['memory']['used']/$status['memory']['total'])*100) }}">
                    <span class="percent">
                        {{ round(($status['memory']['used']/$status['memory']['total'])*100) }}
                    </span>
                </div>
                <div>
                    {{ round(($status['memory']['used']/$status['memory']['total'])*100) }}% of {{ round($status['memory']['total']/1024/1024/1024) }} GB
                </div>

            </div>
            <div class="col-md-3 text-center">
                <h2>Disk</h2>

                <div class="chart" id="diskchart" data-percent="{{ round(($status['disk']['used']/$status['disk']['total'])*100) }}">
                    <span class="percent">
                        {{ round(($status['disk']['used']/$status['disk']['total'])*100) }}
                    </span>
                </div>
                <div>
                    {{ round(($status['disk']['used']/$status['disk']['total'])*100) }}% of {{ round($status['disk']['total']/1024/1024/1024) }} GB
                </div>
            </div>

            <div class="col-md-3 text-center">
                <h2>HA Status</h2>

                @if($status['quorum'] == true)
                    <i class="fa fa-5x fa-check-circle text-success"></i>
                    <div>
                        Quorum OK
                    </div>
                @else
                    <i class="fa fa-5x fa-exclamation-circle text-danger"></i>
                    <div>
                        Quorum FAILED
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>


<style>
    .chart {
        position: relative;
        display: inline-block;
        width: 110px;
        height: 110px;
        margin-top: 0px;
        margin-bottom: 0px;
        text-align: center;
    }
    .chart canvas {
        position: absolute;
        top: 0;
        left: 0;
    }
    .percent {
        display: inline-block;
        line-height: 110px;
        font-size: 22px;
        z-index: 2;
    }
    .percent:after {
        content: '%';
        margin-left: 0;
        font-size: 22px;
    }
</style>

<script type="text/javascript">
    window.onload = function() {
        $('#cpuchart').easyPieChart({
            //your configuration goes here
            animate: 2000,
            barColor: 'green',
            trackColor: '#CCC',
            lineWidth: 10
        });

        $('#memorychart').easyPieChart({
            //your configuration goes here
            animate: 2000,
            barColor: 'blue',
            trackColor: '#CCC',
            lineWidth: 10
        });

        $('#diskchart').easyPieChart({
            //your configuration goes here
            animate: 2000,
            barColor: 'red',
            trackColor: '#CCC',
            lineWidth: 10
        });
    };
</script>


<div class="panel panel-warning">
    <div class="panel-heading">
        Guests and Cluster
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-md-6 text-center">
                <h3>Virtual Machines</h3>

                <table class="text-left table table-condensed">
                    <tbody>
                        <tr>
                            <td>
                                <i class="fa fa-play-circle text-success"></i> Running
                            </td>
                            <td>
                                {{ $status['vms']['running'] }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fa fa-play-circle text-danger"></i> Paused
                            </td>
                            <td>
                                {{ $status['vms']['paused'] }}
                            </td>
                        </tr>
                        <tr>
                            <td valign="left">
                                <i class="fa fa-play-circle text-muted"></i> Stopped
                            </td>
                            <td>
                                {{ $status['vms']['stopped'] }}
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="col-md-6">
                <h3>Node Status</h3>
                <table class="table table-condensed">
                    <tbody>
                        <tr>
                            <td>
                                <i class="fa fa-check-circle text-success"></i> Online</td>
                            <td>{{ $status['online'] }}</td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fa fa-exclamation-triangle text-danger"></i> Offline</td>
                            <td>{{ $status['offline'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>

<div class="panel panel-success">
    <div class="panel-heading">
        Cluster Nodes
    </div>
    <div class="panel-body">
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
                    <td align="right">{{ round($node->memory*100) }}%</td>
                    <td align="right">{{ $node->vmcount }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3"></td>
                <td align="right"><i class="fa fa-tv"></i> <strong>{{ $totalvms }}</strong></td>
            </tr>
        </table>
    </div>
</div>


<div class="panel panel-info">
    <div class="panel-heading">
        Recommendations
    </div>
    <div class="panel-body">
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
    </div>
</div>


@stop