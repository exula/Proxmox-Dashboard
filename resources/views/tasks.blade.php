@extends('layout.main')

@section('content')

    <h1>Currently running tasks</h1>

    <div id="taskTable">
    <table class="table table-striped table-bordered">
    @foreach($tasks as $task)
        <tr>
            <td>
                {{ $task['node'] }}
            </td>
            <td>
                {{ $task['type'] }}
            </td>
            <td>
                @if(isset($task['status']))
                    {{ $task['status'] }}
                @else
                    <i class='fa fa-spin fa-spinner'></i>
                @endif
            </td>
            <td>
                {{ \Carbon\Carbon::createFromTimestamp($task['starttime'])->diffForHumans() }}
            </td>
        </tr>
    @endforeach
    </table>
    </div>

    <script>
        function updateTable()
        {

            $("#taskTable").load("{{ route('tasks') }} #taskTable");

            setTimeout("updateTable()", 2000);
        }

        setTimeout("updateTable()", 2000);

    </script>

@stop
