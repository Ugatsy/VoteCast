@extends('layouts.admin')
@section('title', 'Results — ' . $votingSession->title)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge badge-status-{{ $votingSession->status }} px-3 py-2 me-2" style="border-radius:8px">
            {{ ucfirst($votingSession->status) }}
        </span>
        <span class="text-muted small">
            {{ $votingSession->start_date->format('M d, Y H:i') }} →
            {{ $votingSession->end_date->format('M d, Y H:i') }}
        </span>
    </div>
    <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-sm btn-outline-secondary">
        ← Back to Election
    </a>
</div>

{{-- Turnout Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Voters</div>
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
            <div class="stat-label">Voter Turnout</div>
            <div class="stat-value text-primary">{{ $turnout }}%</div>
        </div>
    </div>
</div>

{{-- Turnout bar --}}
<div class="mb-4">
    <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Turnout</span>
        <span>{{ $totalVoted }} / {{ $totalVoters }}</span>
    </div>
    <div class="progress" style="height:10px;border-radius:5px">
        <div class="progress-bar bg-primary" style="width:{{ $turnout }}%;border-radius:5px"></div>
    </div>
</div>

{{-- Results per position --}}
@foreach($results as $result)
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3 d-flex justify-content-between">
        <strong>{{ $result['position']->title }}</strong>
        <span class="text-muted small">{{ $result['total_votes'] }} total votes</span>
    </div>
    <div class="card-body">
        @forelse($result['candidates'] as $i => $item)
        @php $isWinner = $i === 0 && $result['total_votes'] > 0; @endphp
        <div class="mb-3 p-3 rounded {{ $isWinner ? 'border border-success bg-light' : 'border' }}">
            <div class="d-flex align-items-center gap-3 mb-2">
                <img src="{{ $item['candidate']->photo_url }}"
                     style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid {{ $isWinner ? '#22c55e' : '#e2e8f0' }}"
                     alt="">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold">{{ $item['candidate']->student->full_name }}</span>
                            @if($isWinner)
                                <span class="badge bg-success ms-2" style="font-size:0.7rem">🏆 Winner</span>
                            @endif
                            <div class="text-muted small">{{ $item['candidate']->student->section }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-5">{{ $item['vote_count'] }}</div>
                            <div class="text-muted small">{{ $item['percentage'] }}%</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="progress" style="height:8px;border-radius:4px">
                <div class="progress-bar {{ $isWinner ? 'bg-success' : 'bg-primary' }}"
                     style="width:{{ $item['percentage'] }}%;border-radius:4px;transition:width 1s ease">
                </div>
            </div>
        </div>
        @empty
        <p class="text-muted text-center py-3">No candidates for this position.</p>
        @endforelse
    </div>
</div>
@endforeach

@if(count($results) === 0)
<div class="text-center text-muted py-5">
    <i class="bi bi-bar-chart d-block fs-1 mb-2 opacity-25"></i>
    No positions or votes recorded yet.
</div>
@endif
@endsection
