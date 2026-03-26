@extends('layouts.admin')
@section('title', 'Elections')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage all voting sessions</p>
    <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Election
    </a>
</div>

<div class="card border-0 shadow-sm" style="border-radius:10px">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Positions</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sessions as $s)
            <tr>
                <td class="fw-medium">{{ $s->title }}</td>
                <td class="small text-muted text-capitalize">{{ $s->category }}</td>
                <td>
                    <span class="badge badge-status-{{ $s->status }} px-2 py-1" style="border-radius:6px;font-size:0.78rem">
                        {{ ucfirst($s->status) }}
                    </span>
                </td>
                <td class="text-center">{{ $s->positions->count() }}</td>
                <td class="small text-muted">{{ $s->start_date->format('M d, Y H:i') }}</td>
                <td class="small text-muted">{{ $s->end_date->format('M d, Y H:i') }}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.sessions.show', $s) }}"
                           class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.sessions.candidates', $s) }}"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-person-plus"></i>
                        </a>
                        <a href="{{ route('admin.sessions.results', $s) }}"
                           class="btn btn-outline-success">
                            <i class="bi bi-bar-chart"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-ballot d-block fs-1 mb-2 opacity-25"></i>
                    No elections yet.
                    <a href="{{ route('admin.sessions.create') }}" class="d-block mt-1">Create your first election →</a>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($sessions->hasPages())
    <div class="card-footer bg-white">{{ $sessions->links() }}</div>
    @endif
</div>
@endsection
