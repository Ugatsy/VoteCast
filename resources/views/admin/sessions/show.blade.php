@extends('layouts.admin')
@section('title', $votingSession->title)

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <span class="badge badge-status-{{ $votingSession->status }} px-3 py-2 mb-2" style="border-radius:8px;font-size:0.85rem">
            {{ ucfirst($votingSession->status) }}
        </span>
        <p class="text-muted mb-0 small">
            {{ $votingSession->start_date->format('M d, Y H:i') }} →
            {{ $votingSession->end_date->format('M d, Y H:i') }}
            &nbsp;·&nbsp; {{ ucfirst($votingSession->category) }} election
            @if($votingSession->target_course) &nbsp;·&nbsp; {{ $votingSession->target_course }} @endif
        </p>
    </div>
    <div class="btn-group">
        <a href="{{ route('admin.sessions.candidates', $votingSession) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person-plus me-1"></i>Manage Candidates
        </a>
        <a href="{{ route('admin.sessions.results', $votingSession) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-bar-chart me-1"></i>Results
        </a>
    </div>
</div>

{{-- Status Control --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3"><strong>Change Status</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.sessions.status', $votingSession) }}" class="d-flex gap-2 flex-wrap">
            @csrf
            @foreach(['scheduled','active','paused','completed','cancelled'] as $st)
            <button type="submit" name="status" value="{{ $st }}"
                    class="btn btn-sm {{ $votingSession->status === $st ? 'btn-dark' : 'btn-outline-secondary' }}">
                {{ ucfirst($st) }}
            </button>
            @endforeach
        </form>
    </div>
</div>

{{-- Voter Turnout --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Eligible Voters</div>
            <div class="stat-value">{{ number_format($totalVoters) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Votes Cast</div>
            <div class="stat-value text-success">{{ number_format($totalVoted) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Turnout</div>
            <div class="stat-value text-primary">
                {{ $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 1) : 0 }}%
            </div>
        </div>
    </div>
</div>

{{-- Positions Summary --}}
<div class="card border-0 shadow-sm" style="border-radius:10px">
    <div class="card-header bg-white py-3"><strong>Positions & Candidates</strong></div>
    @forelse($votingSession->positions as $position)
    <div class="card-body border-bottom">
        <h6 class="fw-bold mb-3">{{ $position->title }}</h6>
        <div class="row g-2">
            @forelse($position->candidates as $candidate)
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2 border rounded bg-light">
                    <img src="{{ $candidate->photo_url }}"
                         style="width:40px;height:40px;border-radius:50%;object-fit:cover" alt="">
                    <div>
                        <div class="small fw-medium">{{ $candidate->student->full_name }}</div>
                        <div class="text-muted" style="font-size:0.75rem">{{ $candidate->student->section }}</div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-muted small">No candidates added yet.</div>
            @endforelse
        </div>
    </div>
    @empty
    <div class="card-body text-center text-muted py-4">
        No positions added.
        <a href="{{ route('admin.sessions.candidates', $votingSession) }}">Add positions →</a>
    </div>
    @endforelse
</div>
@endsection
