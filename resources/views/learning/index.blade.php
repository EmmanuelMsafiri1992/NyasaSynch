@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Learning Platform</h1>
            <p>Welcome to the comprehensive learning platform with browser-based IDE!</p>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Courses</h5>
                            <p class="card-text">Browse and enroll in courses</p>
                            <a href="{{ url('learning/courses') }}" class="btn btn-primary">Browse Courses</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Code Editor</h5>
                            <p class="card-text">Practice coding with our online IDE</p>
                            <a href="{{ url('learning/ide') }}" class="btn btn-primary">Open IDE</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Assessments</h5>
                            <p class="card-text">Test your knowledge</p>
                            <a href="{{ url('learning/assessments') }}" class="btn btn-primary">Take Assessment</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection