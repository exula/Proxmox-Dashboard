@extends('layout.main')

@section('content')

    <h1>New VM Provisioning</h1>

    <p>
        New machines will be deployed from templates that then PXE boot behind NAT.
    </p>
    <p>
        <strong>
            Machines will need to be registered in CLAWS before they may be moved to production
        </strong>
    </p>


    <div class="panel panel-info">
        <div class="panel-heading">
            VM Details
        </div>
        <div class="panel-body">
            {!! Form::open(['class' => 'form-horizontal']) !!}

            <p>
                <label for="name">VM Name</label>
                <input type="text" name="name" value="" class="form-control" />
            </p>

            <p>
                <label for="template">Template</label>
                <select name="template" class="form-control">
                    @foreach($templates as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </p>

            <p>
                <label for="storage">Targeted Storage</label>
                <select name="storage" class="form-control">
                    @foreach($storage as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </p>


            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Provision</button>


            {!! Form::close() !!}
        </div>
    </div>


@stop
