<?php
    use Carbon\Carbon;
?>
@extends('layouts.app')

@section('content')
<div class="container">
    @if ($errors->has('email'))
    <div class="alert alert-danger">
        <h4 class="alert-heading">Errr! Please check the error and fix to successfully upload CSV</h4>
        <ul>
            @foreach ($errors->get('email') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{ Form::open(['url' => '/dashboard', 'method' => 'POST']) }}
    <div class="d-flex justify-content-center">
        {{ Form::text('email', '',['class' => 'form-control']) }} &nbsp;
        {{ Form::submit('Get Subscriber Details', ['class' => 'btn btn-primary']) }}
     </div>
    {{ Form::close() }}

    <hr>

    @if (isset($snowballEffect))
    <div class="resultContainer">
        @if (!isset($searchedSubscriber))
            <h3>Sorry, no subscriber found</h3>
        @else
        <h3>Subscriber Details</h3>
        <table class="table">
            <tr>
                <td>Email Address</td>
                <td>{{ $searchedSubscriber->EmailAddress }}</td>
            </tr>
            <tr>
                <td>Name</td>
                <td>{{ $searchedSubscriber->Name }}</td>
            </tr>
            <tr>
                <td>Date</td>
                <td>{{ $searchedSubscriber->Date }}</td>
            </tr>
            <tr>
                <td>State</td>
                <td>{{ $searchedSubscriber->State }}</td>
            </tr>
            @foreach ($searchedSubscriber->CustomFields as $value)
            <tr>
                <td>{{ $value->Key }}</td>
                <td>{{ $value->Value }}</td>
            </tr>
            @endforeach
        </table>
        <br /> <br />

        <h3>Subscriber's Timeline</h3>

        @foreach ($subscriberHistory->response as $value)
        <div class="card border-secondary mb-3">
            <div class="card-header">{{ $value->Name }}</div>
            <div class="card-body text-secondary">
                <div class="d-flex justify-content-around">
                    <div>ID: <strong> {{ $value->ID }} </strong></div>
                    <div>TYPE: <strong> {{ $value->Type }} </strong></div>
                </div>

                @if (count($value->Actions) != 0)
                <h5 class="card-title">Activities</h5>
                <table class="table">
                    <thead>
                        <th class="text-center">Event</th>
                        <th class="text-center">Date</th>
                        <th class="text-center">IP Address</th>
                        <th class="text-center">Details</th>
                    </thead>
                    <tbody>
                        @foreach ($value->Actions as $action)
                        <tr>
                            <td class="text-center">{{ $action->Event }}</td>
                            <td class="text-center">
                                <?php
                                    $date = Carbon::parse($action->Date);
                                    echo $date->toDayDateTimeString();
                                ?>
                            </td>
                            <td class="text-center">{{ $action->IPAddress }}</td>
                            <td>
                                <a href="{{ $action->Detail }}" target="_blank">
                                    {{ $action->Detail }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>


        @endforeach

        @endif
    </div>
    @endif
</div>
@endsection
