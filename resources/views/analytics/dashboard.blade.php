@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Business Intelligence Dashboard</h1>
            <p>Comprehensive analytics and business intelligence for employers</p>

            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Views</h5>
                            <p class="card-text">{{ $overview['total_views'] ?? '0' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Applications</h5>
                            <p class="card-text">{{ $overview['total_applications'] ?? '0' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Active Jobs</h5>
                            <p class="card-text">{{ $overview['active_jobs'] ?? '0' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Conversion Rate</h5>
                            <p class="card-text">{{ $overview['conversion_rate'] ?? '0' }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Quick Actions</h5>
                            <a href="{{ url('analytics/jobs') }}" class="btn btn-primary mr-2">Job Analytics</a>
                            <a href="{{ url('analytics/applications') }}" class="btn btn-info mr-2">Applications</a>
                            <a href="{{ url('analytics/reports') }}" class="btn btn-success">Reports</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Time Period</h5>
                            <select class="form-control" id="periodSelector">
                                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection