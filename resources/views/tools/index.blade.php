@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Career Tools</h1>
            <p>Powerful tools to advance your career and make informed decisions</p>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🎯 Career Quiz</h5>
                            <p class="card-text">Discover your ideal career path with our comprehensive assessment</p>
                            <a href="{{ url('tools/career-quiz') }}" class="btn btn-primary">Take Quiz</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">💰 Salary Calculator</h5>
                            <p class="card-text">Calculate expected salary based on your skills and location</p>
                            <a href="{{ url('tools/salary-calculator') }}" class="btn btn-success">Calculate</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📊 Profile Scoring</h5>
                            <p class="card-text">Get a detailed analysis of your profile strength</p>
                            <a href="{{ url('tools/profile-scoring') }}" class="btn btn-info">Analyze Profile</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🚀 Career Planning</h5>
                            <p class="card-text">Create a strategic plan for your career advancement</p>
                            <a href="{{ url('tools/career-planning') }}" class="btn btn-warning">Plan Career</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection