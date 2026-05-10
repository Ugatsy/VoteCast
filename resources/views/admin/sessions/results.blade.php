@extends('layouts.admin')
@section('title', 'Results — ' . $votingSession->title)

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.sessions.show', $votingSession) }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to election
        </a>
        <h5 class="mb-0 fw-bold mt-1">{{ $votingSession->title }}</h5>
        <div class="d-flex align-items-center gap-2 mt-1">
            <span class="badge badge-status-{{ $votingSession->status }}">{{ ucfirst($votingSession->status) }}</span>
            <span class="text-muted small">
                {{ $votingSession->start_date->format('M d, Y H:i') }} → {{ $votingSession->end_date->format('M d, Y H:i') }}
            </span>
        </div>
    </div>
    <div class="d-flex gap-2">
        @if(in_array($votingSession->status, ['active','completed']))
        <a href="{{ route('admin.sessions.export.excel', $votingSession) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        <a href="{{ route('admin.sessions.export.docx', $votingSession) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-earmark-word me-1"></i>Export Word
        </a>
        @endif
    </div>
</div>

{{-- Turnout cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="vc-stat-card">
            <div class="vc-stat-card-label">Total Eligible Voters</div>
            <div class="vc-stat-card-value">{{ number_format($totalVoters) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="vc-stat-card vc-stat-card--green">
            <div class="vc-stat-card-label">Votes Cast</div>
            <div class="vc-stat-card-value">{{ number_format($totalVoted) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="vc-stat-card vc-stat-card--blue">
            <div class="vc-stat-card-label">Voter Turnout</div>
            <div class="vc-stat-card-value">{{ $turnout }}%</div>
        </div>
    </div>
</div>

{{-- Turnout bar --}}
<div class="card border-0 shadow-sm mb-4 px-4 py-3" style="border-radius:12px">
    <div class="d-flex justify-content-between small mb-2">
        <span class="fw-semibold">Overall Turnout</span>
        <span class="text-muted">{{ $totalVoted }} of {{ $totalVoters }} voters</span>
    </div>
    <div class="progress" style="height:10px;border-radius:6px;background:#e2e8f0">
        <div class="progress-bar" style="width:{{ $turnout }}%;border-radius:6px;background:linear-gradient(90deg,#1a56db,#7c3aed);transition:width 1.2s ease"></div>
    </div>
    @if($turnout >= 75)
        <div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i>Excellent turnout!</div>
    @elseif($turnout >= 50)
        <div class="small text-primary mt-1"><i class="bi bi-info-circle me-1"></i>Good participation.</div>
    @elseif($totalVoters > 0)
        <div class="small text-muted mt-1"><i class="bi bi-hourglass-split me-1"></i>Voting is in progress.</div>
    @endif
</div>

{{-- Results per position --}}
@forelse($results as $result)
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
        <strong>{{ $result['position']->title }}</strong>
        <span class="text-muted small">{{ $result['total_votes'] }} vote(s) cast</span>
    </div>
    <div class="card-body px-4 py-3">
        @forelse($result['candidates'] as $i => $item)
        @php $isWinner = $i === 0 && $result['total_votes'] > 0; @endphp
        <div class="mb-3 p-3 rounded-3 {{ $isWinner ? 'border border-success' : 'border' }}"
             style="{{ $isWinner ? 'background:#f0fdf4' : 'background:#fafafa' }}">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="position-relative">
                    <img src="{{ $item['candidate']->photo_url }}"
                         style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid {{ $isWinner ? '#22c55e' : '#e2e8f0' }}"
                         alt="">
                    @if($isWinner)
                    <span style="position:absolute;bottom:-4px;right:-4px;background:#22c55e;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:0.6rem;color:#fff;border:2px solid #fff">
                        <i class="bi bi-trophy-fill"></i>
                    </span>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-semibold">{{ $item['candidate']->student->full_name }}</span>
                            @if($isWinner)
                                <span class="badge bg-success ms-2" style="font-size:0.7rem">Winner</span>
                            @endif
                            <div class="text-muted small">{{ $item['candidate']->student->section }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-5 lh-1">{{ $item['vote_count'] }}</div>
                            <div class="text-muted small">{{ $item['percentage'] }}%</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="progress" style="height:7px;border-radius:4px;background:#e2e8f0">
                <div class="progress-bar {{ $isWinner ? '' : '' }}"
                     style="width:{{ $item['percentage'] }}%;border-radius:4px;background:{{ $isWinner ? '#22c55e' : '#1a56db' }};transition:width 1.2s ease {{ $i * 0.1 }}s">
                </div>
            </div>
        </div>
        @empty
        <p class="text-muted text-center py-3 mb-0">No candidates for this position.</p>
        @endforelse
    </div>
</div>
@empty
<div class="card border-0 shadow-sm p-5 text-center text-muted" style="border-radius:12px">
    <i class="bi bi-bar-chart d-block fs-1 mb-2 opacity-25"></i>
    <div class="fw-medium">No results yet</div>
    <div class="small mt-1">Positions and votes will appear here once the election is active.</div>
</div>
@endforelse

<style>
.vc-stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
}
.vc-stat-card--green { border-left: 4px solid #22c55e; }
.vc-stat-card--blue  { border-left: 4px solid #1a56db; }
.vc-stat-card-label  { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
.vc-stat-card-value  { font-size: 2rem; font-weight: 700; line-height: 1.15; margin-top: 4px; }
</style>
@endsection

