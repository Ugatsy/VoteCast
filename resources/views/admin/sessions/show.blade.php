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
            @if($votingSession->category === 'course' && $votingSession->target_course)
                &nbsp;·&nbsp; <span class="badge bg-light text-dark border" style="font-size:0.8rem">
                    <i class="bi bi-book me-1"></i>{{ $votingSession->target_course }}
                </span>
            @elseif($votingSession->category === 'section' && $votingSession->target_section)
                &nbsp;·&nbsp; <span class="badge bg-light text-dark border" style="font-size:0.8rem">
                    <i class="bi bi-people me-1"></i>Section {{ $votingSession->target_section }}
                </span>
            @elseif($votingSession->category === 'department' && $votingSession->target_department)
                &nbsp;·&nbsp; <span class="badge bg-light text-dark border" style="font-size:0.8rem">
                    <i class="bi bi-building me-1"></i>{{ $votingSession->target_department }}
                </span>
            @elseif($votingSession->category === 'department' && !$votingSession->target_department)
                &nbsp;·&nbsp; <span class="badge bg-light text-dark border" style="font-size:0.8rem">
                    <i class="bi bi-globe me-1"></i>All Students
                </span>
            @elseif($votingSession->category === 'manual')
                &nbsp;·&nbsp; <span class="badge bg-light text-dark border" style="font-size:0.8rem">
                    <i class="bi bi-person-check me-1"></i>Manual Voter List
                </span>
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="btn-group">
            <a href="{{ route('admin.sessions.candidates', $votingSession) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Manage Candidates
            </a>
            <a href="{{ route('admin.sessions.results', $votingSession) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-bar-chart me-1"></i>Results
            </a>
        </div>

        {{-- ── Export Buttons (shown for active or completed sessions) ── --}}
        @if(in_array($votingSession->status, ['completed', 'active']))
        <div class="btn-group" role="group" aria-label="Export results">
            <a href="{{ route('admin.sessions.export.excel', $votingSession) }}"
               class="btn btn-sm btn-success"
               title="Download results as Excel spreadsheet">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
            </a>
            <a href="{{ route('admin.sessions.export.docx', $votingSession) }}"
               class="btn btn-sm btn-primary"
               title="Download results as Word document">
                <i class="bi bi-file-earmark-word me-1"></i>DOCX
            </a>
        </div>
        @endif
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
            <div class="stat-value" id="totalVoters">{{ number_format($totalVoters) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Votes Cast</div>
            <div class="stat-value text-success" id="totalVoted">{{ number_format($totalVoted) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Turnout</div>
            <div class="stat-value text-primary" id="turnout">
                {{ $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 1) : 0 }}%
            </div>
        </div>
    </div>
</div>

{{-- Completed export call-to-action banner --}}
@if($votingSession->status === 'completed')
<div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 mb-4" style="border-radius:10px">
    <i class="bi bi-trophy-fill fs-4 text-success"></i>
    <div class="flex-grow-1">
        <strong>Election Completed!</strong>
        <span class="text-muted ms-2 small">Download the official results for your records or for distribution.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.sessions.export.excel', $votingSession) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download Excel
        </a>
        <a href="{{ route('admin.sessions.export.docx', $votingSession) }}"
           class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-word me-1"></i>Download DOCX
        </a>
    </div>
</div>
@endif

{{-- Positions Summary with Real-time Vote Counts --}}
<div class="card border-0 shadow-sm" style="border-radius:10px">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <strong>Positions & Candidates</strong>
        <span class="badge bg-primary" id="lastUpdate">Just now</span>
    </div>
    @forelse($votingSession->positions as $position)
    <div class="card-body border-bottom" data-position-id="{{ $position->id }}">
        <h6 class="fw-bold mb-3">
            {{ $position->title }}
            @php
                $positionTotalVotes = $position->candidates->sum('votes_count');
            @endphp
            <span class="badge bg-secondary ms-2">{{ number_format($positionTotalVotes) }} total votes</span>
        </h6>
        <div class="row g-3">
            @forelse($position->candidates as $candidate)
            <div class="col-md-4" data-candidate-id="{{ $candidate->id }}">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="{{ $candidate->photo_url }}"
                                 style="width:50px;height:50px;border-radius:50%;object-fit:cover" alt="">
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ $candidate->student->full_name }}</div>
                                <div class="text-muted" style="font-size:0.75rem">{{ $candidate->student->section }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary h4 mb-0" id="votes-{{ $candidate->id }}">
                                    {{ number_format($candidate->votes_count) }}
                                </div>
                                <div class="text-muted" style="font-size:0.7rem">votes</div>
                            </div>
                        </div>
                        @if($candidate->manifesto)
                            <div class="small text-muted mt-2">
                                <i class="bi bi-chat-quote"></i> {{ Str::limit($candidate->manifesto, 100) }}
                            </div>
                        @endif
                        {{-- Progress Bar --}}
                        @php
                            $percentage = $positionTotalVotes > 0 ? ($candidate->votes_count / $positionTotalVotes * 100) : 0;
                        @endphp
                        <div class="mt-2">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped bg-primary"
                                     id="progress-{{ $candidate->id }}"
                                     style="width: {{ $percentage }}%">
                                </div>
                            </div>
                            <div class="text-end small text-muted mt-1">{{ round($percentage, 1) }}%</div>
                        </div>
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

<style>
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1rem 1.2rem;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}
.stat-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 0.25rem;
}
.badge-status-active { background: #22c55e20; color: #15803d; }
.badge-status-scheduled { background: #eab30820; color: #a16207; }
.badge-status-paused { background: #f9731620; color: #9a3412; }
.badge-status-completed { background: #3b82f620; color: #1e40af; }
.badge-status-cancelled { background: #ef444420; color: #991b1b; }
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.05); }
}
.refreshing {
    animation: pulse 0.3s ease-in-out;
}
.progress-bar {
    transition: width 0.3s ease-in-out;
}
</style>

@push('scripts')
<script>
class VoteMonitor {
    constructor(sessionId) {
        this.sessionId = sessionId;
        this.pollingInterval = null;
        this.isRefreshing = false;

        this.init();
    }

    init() {
        this.startPolling();

        // Auto-refresh every 3 seconds
        this.pollingInterval = setInterval(() => this.fetchVotes(), 3000);

        // Stop polling when page is hidden to save resources
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
            } else {
                if (!this.pollingInterval) {
                    this.pollingInterval = setInterval(() => this.fetchVotes(), 3000);
                    this.fetchVotes();
                }
            }
        });
    }

    async fetchVotes() {
        if (this.isRefreshing) return;

        this.isRefreshing = true;

        try {
            const response = await fetch(`/admin/api/sessions/${this.sessionId}/votes`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();
            this.updateUI(data);

            const lastUpdateSpan = document.getElementById('lastUpdate');
            if (lastUpdateSpan) {
                lastUpdateSpan.textContent = 'Just now';
                lastUpdateSpan.classList.add('refreshing');
                setTimeout(() => lastUpdateSpan.classList.remove('refreshing'), 500);
            }

        } catch (error) {
            console.error('Error fetching votes:', error);
        } finally {
            this.isRefreshing = false;
        }
    }

    updateUI(data) {
        if (data.total_voted !== undefined) {
            const totalVotedEl = document.getElementById('totalVoted');
            if (totalVotedEl) {
                totalVotedEl.textContent = new Intl.NumberFormat().format(data.total_voted);
                totalVotedEl.classList.add('refreshing');
                setTimeout(() => totalVotedEl.classList.remove('refreshing'), 500);
            }

            const turnoutEl = document.getElementById('turnout');
            if (turnoutEl && data.total_voters) {
                const turnout = ((data.total_voted / data.total_voters) * 100).toFixed(1);
                turnoutEl.textContent = `${turnout}%`;
                turnoutEl.classList.add('refreshing');
                setTimeout(() => turnoutEl.classList.remove('refreshing'), 500);
            }
        }

        if (data.candidates) {
            Object.entries(data.candidates).forEach(([candidateId, voteCount]) => {
                const voteEl = document.getElementById(`votes-${candidateId}`);
                if (voteEl) {
                    voteEl.textContent = new Intl.NumberFormat().format(voteCount);
                    voteEl.classList.add('refreshing');
                    setTimeout(() => voteEl.classList.remove('refreshing'), 500);

                    const progressEl = document.getElementById(`progress-${candidateId}`);
                    if (progressEl && data.progress_bars && data.progress_bars[candidateId] !== undefined) {
                        progressEl.style.width = `${data.progress_bars[candidateId]}%`;
                        progressEl.classList.add('refreshing');
                        setTimeout(() => progressEl.classList.remove('refreshing'), 500);

                        const percentSpan = progressEl.closest('.mt-2')?.querySelector('.text-end .small');
                        if (percentSpan) {
                            percentSpan.textContent = `${data.progress_bars[candidateId].toFixed(1)}%`;
                        }
                    }
                }
            });
        }

        if (data.position_totals) {
            Object.entries(data.position_totals).forEach(([positionId, totalVotes]) => {
                const positionCard = document.querySelector(`[data-position-id="${positionId}"]`);
                if (positionCard) {
                    const totalSpan = positionCard.querySelector('.badge.bg-secondary');
                    if (totalSpan) {
                        totalSpan.textContent = new Intl.NumberFormat().format(totalVotes) + ' total votes';
                        totalSpan.classList.add('refreshing');
                        setTimeout(() => totalSpan.classList.remove('refreshing'), 500);
                    }
                }
            });
        }
    }

    startPolling() {
        this.fetchVotes();
    }

    stop() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const voteMonitor = new VoteMonitor({{ $votingSession->id }});

    window.addEventListener('beforeunload', () => {
        if (voteMonitor) voteMonitor.stop();
    });
});
</script>
@endpush
@endsection
