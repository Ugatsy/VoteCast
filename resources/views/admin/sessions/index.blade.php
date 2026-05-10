@extends('layouts.admin')
@section('title', 'Elections')

@section('content')

{{-- Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold">Elections</h5>
        <p class="text-muted small mb-0">Create and manage all voting sessions</p>
    </div>
    <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Election
    </a>
</div>

{{-- Summary Stats Strip — counts from DB, not from paginated page --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="vc-mini-stat">
            <div class="vc-mini-stat-icon" style="background:#eff6ff;color:#1a56db">
                <i class="bi bi-collection-fill"></i>
            </div>
            <div>
                <div class="vc-mini-stat-val">{{ $totalCount }}</div>
                <div class="vc-mini-stat-lbl">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="vc-mini-stat">
            <div class="vc-mini-stat-icon" style="background:#f0fdf4;color:#16a34a">
                <i class="bi bi-broadcast-pin"></i>
            </div>
            <div>
                <div class="vc-mini-stat-val">{{ $activeCount }}</div>
                <div class="vc-mini-stat-lbl">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="vc-mini-stat">
            <div class="vc-mini-stat-icon" style="background:#fefce8;color:#ca8a04">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="vc-mini-stat-val">{{ $scheduledCount }}</div>
                <div class="vc-mini-stat-lbl">Scheduled</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="vc-mini-stat">
            <div class="vc-mini-stat-icon" style="background:#f5f3ff;color:#7c3aed">
                <i class="bi bi-check2-all"></i>
            </div>
            <div>
                <div class="vc-mini-stat-val">{{ $completedCount }}</div>
                <div class="vc-mini-stat-lbl">Completed</div>
            </div>
        </div>
    </div>
</div>

{{-- How-it-works tip --}}
<div class="vc-tip mb-4">
    <i class="bi bi-info-circle-fill me-2 text-primary flex-shrink-0 mt-1"></i>
    <span class="small">
        <strong>How elections work:</strong>
        Create an election → Add positions &amp; candidates → Set status to <strong>Active</strong> to open voting → Monitor results live → Set to <strong>Completed</strong> when done.
    </span>
</div>

{{-- Search + Filter bar --}}
<form method="GET" action="{{ route('admin.sessions.index') }}" id="filterForm">
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">

            {{-- Status pill filters --}}
            <span class="small text-muted me-1 flex-shrink-0">Filter:</span>
            @foreach(['all' => 'All', 'scheduled' => 'Scheduled', 'active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
            <button type="submit" name="status" value="{{ $val === 'all' ? '' : $val }}"
                    class="btn btn-sm {{ request('status', '') === ($val === 'all' ? '' : $val) ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="border-radius:20px;font-size:0.78rem;padding:.25rem .85rem">
                @if($val === 'active' && $activeCount > 0)
                    <span class="live-dot me-1"></span>
                @endif
                {{ $label }}
                @if($val === 'active' && $activeCount > 0)
                    <span class="badge bg-success ms-1 rounded-pill" style="font-size:0.65rem">{{ $activeCount }}</span>
                @endif
            </button>
            @endforeach

            {{-- Search input --}}
            <div class="ms-auto d-flex gap-2">
                {{-- Preserve current status when searching --}}
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="input-group input-group-sm" style="width:220px">
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control" style="border-radius:20px 0 0 20px"
                           placeholder="Search elections…">
                    <button class="btn btn-outline-primary" type="submit"
                            style="border-radius:0 20px 20px 0">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                @if(request('search') || request('status'))
                <a href="{{ route('admin.sessions.index') }}"
                   class="btn btn-sm btn-outline-danger" style="border-radius:20px" title="Clear filters">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
</form>

{{-- Elections Table --}}
<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4" style="width:40%">Election</th>
                    <th>Status</th>
                    <th>Positions</th>
                    <th>Schedule</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sessions as $s)
            <tr>
                <td class="ps-4 py-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="vc-status-dot vc-status-dot--{{ $s->status }} flex-shrink-0 mt-1"></div>
                        <div>
                            <a href="{{ route('admin.sessions.show', $s) }}"
                               class="fw-semibold text-dark text-decoration-none">
                                {{ $s->title }}
                            </a>
                            @if($s->description)
                            <div class="text-muted small text-truncate" style="max-width:320px">
                                {{ $s->description }}
                            </div>
                            @endif
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span class="badge bg-light text-secondary border" style="font-size:0.7rem;text-transform:capitalize">
                                    <i class="bi bi-people me-1"></i>{{ $s->category }}
                                </span>
                                @if($s->target_course)
                                    <span class="badge bg-light text-secondary border" style="font-size:0.7rem">{{ $s->target_course }}</span>
                                @endif
                                @if($s->target_section)
                                    <span class="badge bg-light text-secondary border" style="font-size:0.7rem">{{ $s->target_section }}</span>
                                @endif
                                @if($s->target_department)
                                    <span class="badge bg-light text-secondary border" style="font-size:0.7rem">{{ $s->target_department }}</span>
                                @endif
                                @if($s->requires_release_code)
                                    <span class="badge bg-warning text-dark" style="font-size:0.7rem">
                                        <i class="bi bi-key me-1"></i>Code required
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-status-{{ $s->status }} px-2 py-1" style="border-radius:6px;font-size:0.78rem">
                        @if($s->status === 'active')<span class="live-dot me-1"></span>@endif
                        {{ ucfirst($s->status) }}
                    </span>
                </td>
                <td>
                    @php $posCount = $s->positions->count(); @endphp
                    <div class="d-flex align-items-center gap-2">
                        @if($posCount > 0)
                        <div class="vc-pos-bubbles">
                            @foreach($s->positions->take(3) as $pos)
                                <span class="vc-pos-bubble" title="{{ $pos->title }}">
                                    {{ strtoupper(substr($pos->title, 0, 1)) }}
                                </span>
                            @endforeach
                            @if($posCount > 3)
                                <span class="vc-pos-bubble vc-pos-bubble--more">+{{ $posCount - 3 }}</span>
                            @endif
                        </div>
                        @endif
                        <span class="small text-muted">{{ $posCount }} position(s)</span>
                    </div>
                </td>
                <td>
                    <div class="small">
                        <div class="text-muted">
                            <i class="bi bi-play-circle me-1 text-success opacity-75"></i>
                            {{ $s->start_date->format('M d, Y H:i') }}
                        </div>
                        <div class="text-muted">
                            <i class="bi bi-stop-circle me-1 text-danger opacity-75"></i>
                            {{ $s->end_date->format('M d, Y H:i') }}
                        </div>
                        @if($s->status === 'active')
                            <div class="text-success mt-1" style="font-size:0.72rem">
                                <i class="bi bi-clock me-1"></i>Ends {{ $s->end_date->diffForHumans() }}
                            </div>
                        @elseif($s->status === 'scheduled')
                            <div class="text-warning mt-1" style="font-size:0.72rem">
                                <i class="bi bi-hourglass-split me-1"></i>Starts {{ $s->start_date->diffForHumans() }}
                            </div>
                        @endif
                    </div>
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="{{ route('admin.sessions.show', $s) }}"
                           class="btn btn-sm btn-outline-primary" title="View election">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.sessions.candidates', $s) }}"
                           class="btn btn-sm btn-outline-secondary" title="Manage candidates">
                            <i class="bi bi-person-plus"></i>
                        </a>
                        <a href="{{ route('admin.sessions.results', $s) }}"
                           class="btn btn-sm {{ $s->status === 'active' ? 'btn-success' : 'btn-outline-success' }}"
                           title="View results">
                            <i class="bi bi-bar-chart"></i>
                        </a>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                title="Delete election"
                                @if($s->status === 'active') disabled title="Cannot delete an active election" @endif
                                onclick="confirmDelete({{ $s->id }}, '{{ addslashes($s->title) }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-ballot d-block fs-1 mb-2 opacity-25"></i>
                        <div class="fw-medium mb-1">
                            @if(request('search') || request('status'))
                                No elections match your filters
                            @else
                                No elections yet
                            @endif
                        </div>
                        @if(request('search') || request('status'))
                            <a href="{{ route('admin.sessions.index') }}" class="btn btn-sm btn-outline-secondary mt-1">
                                <i class="bi bi-x me-1"></i>Clear filters
                            </a>
                        @else
                            <a href="{{ route('admin.sessions.create') }}" class="btn btn-sm btn-primary mt-1">
                                Create your first election →
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="card-footer bg-white border-0 px-4 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Showing {{ $sessions->firstItem() }} – {{ $sessions->lastItem() }} of {{ $sessions->total() }} elections
                @if(request('search'))
                    matching <strong>"{{ request('search') }}"</strong>
                @endif
            </div>
            {{ $sessions->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

<style>
/* ── Mini stat strip ─────────────────────────────── */
.vc-mini-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: .85rem 1rem;
    display: flex;
    align-items: center;
    gap: .85rem;
}
.vc-mini-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.vc-mini-stat-val { font-size: 1.45rem; font-weight: 700; line-height: 1.1; }
.vc-mini-stat-lbl { font-size: 0.72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }

/* ── Tip ─────────────────────────────────────────── */
.vc-tip {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: .75rem 1rem;
    display: flex;
    align-items: flex-start;
    gap: .25rem;
}

/* ── Status dot ──────────────────────────────────── */
.vc-status-dot {
    width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; margin-top: 4px;
}
.vc-status-dot--active    { background: #22c55e; box-shadow: 0 0 0 3px #dcfce7; animation: pulse-dot 1.6s infinite; }
.vc-status-dot--scheduled { background: #f59e0b; }
.vc-status-dot--paused    { background: #94a3b8; }
.vc-status-dot--completed { background: #1a56db; }
.vc-status-dot--cancelled { background: #ef4444; }

/* ── Position bubbles ────────────────────────────── */
.vc-pos-bubbles { display: flex; gap: 3px; }
.vc-pos-bubble {
    width: 24px; height: 24px; border-radius: 50%;
    background: #eff6ff; color: #1a56db;
    font-size: 0.68rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid #bfdbfe;
}
.vc-pos-bubble--more { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

/* ── Live dot ────────────────────────────────────── */
.live-dot {
    display: inline-block; width: 7px; height: 7px;
    border-radius: 50%; background: currentColor;
    animation: pulse-dot 1.5s infinite;
    vertical-align: middle;
}
@keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(1.35); }
}

/* ── Pagination trim ─────────────────────────────── */
.pagination { margin-bottom: 0; }
.pagination .page-item:not(:first-child):not(:last-child) { display: none !important; }
.pagination .page-item:first-child { margin-right: .5rem; }
</style>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-danger" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Election
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-1">You are about to permanently delete:</p>
                <p class="fw-semibold mb-3" id="deleteSessionTitle"></p>
                <div class="alert alert-danger border-0 small mb-0" style="border-radius:8px">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    This will also delete all positions, candidates, votes, and participation records.
                    <strong>This cannot be undone.</strong>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Yes, delete permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteSessionTitle').textContent = '"' + title + '"';
    document.getElementById('deleteForm').action = '/admin/sessions/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
