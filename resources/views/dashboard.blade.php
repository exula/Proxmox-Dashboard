@extends('layout.main')

@section('content')
<h1>Dashboard</h1>

<div class="panel panel-default">
    <div class="panel-heading">
        Cluster Resources
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12 text-center">
                <h2>CPU</h2>

                <div class="chart" id="cpuchart" data-percent="0">
                    <span class="percent">0
                    </span>
                </div>
                <div>
                    <span id="cpuused">0</span>% of <span id="cputotal"></span> CPU(s)
                </div>

            </div>
            <div class="col-md-3 col-sm-6 col-xs-12 text-center">
                <h2>Memory</h2>

                <div class="chart" id="memorychart" data-percent="0">
                    <span class="percent">
                        0
                    </span>
                </div>
                <div>
                   <span id="memoryused">0</span>% of <span id="memorytotal"></span>GB
                </div>

            </div>
            <div class="col-md-3 col-sm-6 col-xs-12 text-center">
                <h2>Disk</h2>

                <div class="chart" id="diskchart" data-percent="0">
                    <span class="percent">
                        0
                    </span>
                </div>
                <div>
                    <span id="diskused">0</span>% of <span id="disktotal">0</span> GB
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12 text-center">
                <h2>HA Status</h2>

                <div id="quorum_success" >
                    <i class="fa fa-5x fa-check-circle text-success"></i>
                    <div>
                        Quorum OK
                    </div>
                </div>

                <div id="quorum_failed" style="display: none">
                    <i class="fa fa-5x fa-exclamation-circle text-danger"></i>
                    <div>
                        Quorum FAILED
                    </div>
                </div>


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


        getData();

        window.setInterval(getData, 1000);

    };

    function getData()
    {
        $.ajax({
                    "url": '{{ route('dashboardData') }}'
                }
        ).success(function(data){

            initEasyPie();
            updateDashboard(data);

        });
    }

    function updateDashboard(data)
    {

        cpuused = data.status.cpu.used;
        cputotal = data.status.cpu.total;

        $('#cpuchart').data('easyPieChart').update(cpuused);
        $('#cpuchart').attr("data-percent", cpuused);
        $('#cpuchart .percent').html(cpuused);
        $('#cpuused').html(cpuused);
        $('#cputotal').html(cputotal);


        memused = data.status.memory.used;
        memtotal = data.status.memory.total;

        $('#memorychart').data('easyPieChart').update(memused);
        $('#memorychart').attr("data-percent", memused);
        $('#memorychart .percent').html(memused);
        $('#memoryused').html(memused);
        $('#memorytotal').html(memtotal);

        diskused = data.status.disk.used;
        disktotal = data.status.disk.total;


        $('#diskchart').data('easyPieChart').update(diskused);
        $('#diskchart').attr("data-percent", diskused);
        $('#diskchart .percent').html(diskused);
        $('#diskused').html(diskused);
        $('#disktotal').html(disktotal);

        if(data.status.quorum == true)
        {
            $("#quorum_success").show();
            $("#quorum_failed").hide();
        } else {
            $("#quorum_success").hide();
            $("#quorum_failed").show();
        }

        $('#status_vm_running').html(data.status.vms.running);
        $('#status_vm_paused').html(data.status.vms.paused);
        $('#status_vm_stopped').html(data.status.vms.stopped);

        $('#status_online').html(data.status.online);
        $('#status_offline').html(data.status.offline);

        $('#totalvms').html(data.totalvms);

        updateRecommendations(data);

        updateNodes(data);



    }

    function updateNodes(data)
    {

        html = '';

        for ( node in data.nodes)
        {
            mynode = data.nodes[node];

            html += '<tr><td><i class="fa fa-server"></i> '+mynode.name+'</td> \
            <td align="right">'+mynode.load+'%</td> \
            <td align="right">'+(mynode.memory * 100).toFixed(2)+'%</td> \
            <td align="right">'+mynode.vmcount+'</td> \
            </tr>';

        }

        $("#nodestbody").html(html);

    }

    function updateRecommendations(data)
    {

        html = "<ul>";
        for( id in data.recommendations)
        {
            console.log(data.recommendations[id]);

            html += "<li>"+data.recommendations[id]+"</li>";

        }

        html += "</ul>"

        $("#recommendations").html(html);

        recommendJSON = JSON.stringify(data.recommendations);
        console.log(recommendJSON);
        $("#recommendationsjson").val( recommendJSON );

    }

    function initEasyPie()
    {
        $('#cpuchart').easyPieChart({
            //your configuration goes here
            animate: 900,
            barColor: 'green',
            trackColor: '#CCC',
            lineWidth: 10
        });


        $('#memorychart').easyPieChart({
            //your configuration goes here
            animate: 900,
            barColor: 'blue',
            trackColor: '#CCC',
            lineWidth: 10
        });

        $('#diskchart').easyPieChart({
            //your configuration goes here
            animate: 900,
            barColor: 'red',
            trackColor: '#CCC',
            lineWidth: 10
        });
    }

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
                                <span id="status_vm_running">0</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fa fa-play-circle text-danger"></i> Paused
                            </td>
                            <td>
                                <span id="status_vm_paused">0</span>
                            </td>
                        </tr>
                        <tr>
                            <td valign="left">
                                <i class="fa fa-play-circle text-muted"></i> Stopped
                            </td>
                            <td>
                                <span id="status_vm_stopped">0</span>
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
                            <td><span id="status_online"></span></td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fa fa-exclamation-triangle text-danger"></i> Offline</td>
                            <td><span id="status_offline"></span></td>
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
            <tbody id="nodestbody">

            </tbody>
            <tfoot>
            <tr>
                <td colspan="3"></td>
                <td align="right"><i class="fa fa-tv"></i> <strong><span id="totalvms"></span></strong></td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>


<div class="panel panel-info">
    <div class="panel-heading">
        Recommendations
    </div>
    <div class="panel-body">
        <p id="recommendations">

        </p>

        {!! Form::open(array('route' => 'dorecommendations')) !!}
        <input type="hidden" id=recommendationsjson name="recommendations" value="">
        <button type="submit">Do Recommendations</button>
        {!! Form::close() !!}
    </div>
</div>


@stop