@extends('layout.main')

@section('content')

    <h1>Current Virtual Machines</h1>

    <div id="taskTable">
        <table class="table table-striped table-bordered">
            @foreach($virtualmachines  as $vm)

                @if($vm->template != 1)
                    <tr>
                        <td>
                            @if($vm->status == 'running')
                                <i class="fa fa-play text-success"></i>
                            @else
                                <i class="fa fa-pause text-default"></i>
                            @endif
                        </td>
                        <td>
                            <a href="{{ App\Node::qemuLink($vm->vmid) }}" target="_blank">
                                {{ $vm->vmid }}
                            </a>
                        </td>
                        <td>
                            {{ $vm->name }}
                        </td>
                        <td>
                            {{ $vm->cpus }}
                        </td>
                        <td>
                            {{ round($vm->maxmem/1024/1024) }} MB
                        </td>
                        <td>
                            @if(isset($vm->config->net0))
                                <?php
                                    $mac = preg_split('/=|,/', $vm->config->net0);

                                    $link = str_replace('MACADDRESS', $mac[1], env('NETWORK_LINK'), $mac[1]);
                                ?>

                                <a href="{{ $link }}" TARGET="_BLANK">View Network Link</a>

                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    </div>
@endsection
