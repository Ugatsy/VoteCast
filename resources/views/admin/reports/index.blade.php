{{-- resources/views/admin/reports/index.blade.php --}}
@extends('layouts.admin')
@section('title', 'Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Election Reports</h5>
        <p class="text-muted small mb-0">View and export results from completed elections</p>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Election Title</th>
                        <th>Status</th>
                        <th>Period</th>
                        <th>Turnout</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $session->title }}</div>
                            <div class="small text-muted">{{ $session->description ?? 'No description' }}</div>
                        </td>
                        <td>
                            <span class="badge badge-status-{{ $session->status }}">
                                {{ ucfirst($session->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="small">
                                <div>{{ $session->start_date->format('M d, Y') }}</div>
                                <div class="text-muted">→ {{ $session->end_date->format('M d, Y') }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $session->turnout_percentage }}%</div>
                            <div class="small text-muted">{{ number_format($session->total_votes_cast) }} / {{ number_format($session->total_voters) }} votes</div>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.reports.show', $session) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Report
                                </a>
                                <a href="{{ route('admin.sessions.export.excel', $session) }}"
                                   class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-file-excel"></i> Excel
                                </a>
                                <a href="{{ route('admin.sessions.export.docx', $session) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-file-word"></i> Word
                                </a>
                            </div>
                        </td>
                     </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-file-text fs-1 d-block mb-2 opacity-25"></i>
                            No completed elections yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $sessions->links() }}
    </div>
</div>

<style>
.badge-status-active    { background: #dcfce7; color: #166534; }
.badge-status-scheduled { background: #fef9c3; color: #713f12; }
.badge-status-completed { background: #dbeafe; color: #1e40af; }
.badge-status-paused    { background: #ffedd5; color: #9a3412; }
.badge-status-cancelled { background: #fee2e2; color: #991b1b; }
</style>
@endsection
