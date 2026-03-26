@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

{{-- Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Students</div>
            <div class="stat-value">{{ number_format($stats['total_students']) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Current Enrollment</div>
            <div class="stat-value text-primary">{{ number_format($stats['enrollments']) }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Active Elections</div>
            <div class="stat-value text-success">{{ $stats['active_sessions'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Votes Cast</div>
            <div class="stat-value" style="color:#7c3aed">{{ number_format($stats['total_votes']) }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Recent Elections --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:10px">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3" style="border-radius:10px 10px 0 0">
                <strong>Recent Elections</strong>
                <a href="{{ route('admin.sessions.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>New Election
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Start</th>
                            <th>End</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentSessions as $s)
                    <tr>
                        <td class="fw-medium">{{ $s->title }}</td>
                        <td>
                            <span class="badge badge-status-{{ $s->status }} px-2 py-1" style="border-radius:6px;font-size:0.78rem">
                                {{ ucfirst($s->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $s->start_date->format('M d, Y') }}</td>
                        <td class="text-muted small">{{ $s->end_date->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.sessions.show', $s) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-ballot d-block fs-2 mb-2 opacity-25"></i>
                            No elections created yet.
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($recentSessions->count())
            <div class="card-footer bg-white text-center py-2">
                <a href="{{ route('admin.sessions.index') }}" class="text-primary small">View all elections →</a>
            </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:10px">
            <div class="card-header bg-white py-3"><strong>Quick Actions</strong></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.enrollment.index') }}" class="btn btn-outline-primary text-start">
                    <i class="bi bi-upload me-2"></i>Upload Enrollment Excel
                </a>
                <a href="{{ route('admin.sessions.create') }}" class="btn btn-outline-success text-start">
                    <i class="bi bi-plus-circle me-2"></i>Create New Election
                </a>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline-secondary text-start">
                    <i class="bi bi-list-ul me-2"></i>Manage Elections
                </a>
            </div>
        </div>

        @if($activeSessions->count())
        <div class="card border-0 shadow-sm" style="border-radius:10px; border-left: 4px solid #22c55e !important">
            <div class="card-header bg-white py-3">
                <strong class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:0.6rem"></i>Live Elections</strong>
            </div>
            <div class="list-group list-group-flush">
                @foreach($activeSessions as $active)
                <a href="{{ route('admin.sessions.results', $active) }}"
                   class="list-group-item list-group-item-action py-2 px-3">
                    <div class="small fw-medium">{{ $active->title }}</div>
                    <div class="text-muted" style="font-size:0.75rem">
                        {{ $active->positions->count() }} position(s) · Ends {{ $active->end_date->diffForHumans() }}
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
