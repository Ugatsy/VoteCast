@extends('layouts.admin')
@section('title', 'Report — ' . $votingSession->title)

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.reports.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to Reports
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
        <a href="{{ route('admin.sessions.export.excel', $votingSession) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        <a href="{{ route('admin.sessions.export.docx', $votingSession) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-earmark-word me-1"></i>Export Word
        </a>
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
@php
    $position = $result['position'];
    $totalVotes = $result['total_votes'];
    $candidates = $result['candidates'];
    $maxWinners = $position->max_winners;

    // Get total participants who cast any votes in this session
    $totalParticipants = $votingSession->total_votes_cast;

    // Abstained for this position = participants who didn't vote for this position
    $abstainCount = $totalParticipants - $totalVotes;
    $abstainPercentage = $totalParticipants > 0 ? round(($abstainCount / $totalParticipants) * 100, 2) : 0;

    // Calculate winners
    $winners = [];
    if ($candidates->count() > 0) {
        $sortedCandidates = $candidates->sortByDesc('vote_count')->values();
        $topCandidates = $sortedCandidates->take($maxWinners);
        $winnerVoteCounts = $topCandidates->pluck('vote_count')->toArray();

        if ($sortedCandidates->count() > $maxWinners) {
            $lastWinnerVoteCount = $winnerVoteCounts[$maxWinners - 1] ?? 0;
            $nextCandidateVoteCount = $sortedCandidates[$maxWinners]['vote_count'] ?? 0;

            if ($lastWinnerVoteCount == $nextCandidateVoteCount) {
                $winners = $sortedCandidates->filter(function($candidate) use ($lastWinnerVoteCount) {
                    return $candidate['vote_count'] == $lastWinnerVoteCount;
                })->pluck('candidate.id')->toArray();
            } else {
                $winners = $topCandidates->pluck('candidate.id')->toArray();
            }
        } else {
            $winners = $topCandidates->pluck('candidate.id')->toArray();
        }
    }
@endphp
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
        <div>
            <strong>{{ $position->title }}</strong>
            @if($maxWinners > 1)
                <span class="badge bg-primary ms-2">{{ $maxWinners }} winner(s)</span>
            @endif
        </div>
        <div class="d-flex gap-3">
            <span class="text-muted small">
                <i class="bi bi-check2-circle me-1"></i>{{ number_format($totalVotes) }} vote(s) cast
            </span>
            @if($abstainCount > 0)
            <a href="#" class="text-muted small text-decoration-none view-abstain-btn"
               data-position-id="{{ $position->id }}"
               data-position-title="{{ $position->title }}"
               data-abstain-count="{{ $abstainCount }}"
               data-total-participants="{{ $totalParticipants }}">
                <i class="bi bi-x-circle me-1"></i>{{ number_format($abstainCount) }} abstained
                <span class="text-muted">(did not vote for this position)</span>
            </a>
            @endif
        </div>
    </div>
    <div class="card-body px-4 py-3">
        @forelse($candidates as $item)
        @php
            $isWinner = in_array($item['candidate']->id, $winners);
            $candidate = $item['candidate'];
        @endphp
        <div class="mb-3 p-3 rounded-3 candidate-card {{ $isWinner ? 'border border-success' : 'border' }}"
             style="{{ $isWinner ? 'background:#f0fdf4' : 'background:#fafafa' }};cursor:pointer"
             data-candidate-id="{{ $candidate->id }}"
             data-candidate-name="{{ $candidate->student->full_name ?? $candidate->full_name }}"
             data-candidate-section="{{ $candidate->student->section ?? 'N/A' }}"
             data-vote-count="{{ number_format($item['vote_count']) }}">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="position-relative">
                    <img src="{{ $candidate->photo_url }}"
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
                            <span class="fw-semibold">{{ $candidate->student->full_name ?? $candidate->full_name }}</span>
                            @if($isWinner)
                                <span class="badge bg-success ms-2" style="font-size:0.7rem">
                                    <i class="bi bi-trophy me-1"></i>Winner
                                </span>
                            @endif
                            <div class="text-muted small">{{ $candidate->student->section ?? 'N/A' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-5 lh-1">{{ number_format($item['vote_count']) }}</div>
                            <div class="text-muted small">{{ $item['percentage'] }}%</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="progress" style="height:7px;border-radius:4px;background:#e2e8f0">
                <div class="progress-bar {{ $isWinner ? 'bg-success' : 'bg-primary' }}"
                     style="width:{{ $item['percentage'] }}%;border-radius:4px;transition:width 1.2s ease {{ $loop->index * 0.1 }}s">
                </div>
            </div>
            <div class="small text-muted mt-2 text-end">
                <i class="bi bi-people me-1"></i>Click to view voters
            </div>
        </div>
        @empty
        <p class="text-muted text-center py-3 mb-0">No candidates for this position.</p>
        @endforelse

        {{-- Show abstain bar --}}
        @if($totalParticipants > 0)
        <div class="mt-3 pt-2 border-top">
            <div class="d-flex justify-content-between small mb-1 text-muted">
                <span>Abstained (participated but didn't vote for this position)</span>
                <span>{{ number_format($abstainCount) }} voters ({{ $abstainPercentage }}%)</span>
            </div>
            <div class="progress" style="height:7px;border-radius:4px;background:#e2e8f0">
                <div class="progress-bar bg-secondary"
                     style="width:{{ $abstainPercentage }}%;border-radius:4px">
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@empty
<div class="card border-0 shadow-sm p-5 text-center text-muted" style="border-radius:12px">
    <i class="bi bi-bar-chart d-block fs-1 mb-2 opacity-25"></i>
    <div class="fw-medium">No results yet</div>
    <div class="small mt-1">Positions and votes will appear here once the election is active.</div>
</div>
@endforelse

{{-- Voter Tracking Section --}}
<div class="card border-0 shadow-sm mt-4" style="border-radius:12px">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong><i class="bi bi-people me-2 text-primary"></i>Voter Tracking</strong>
            <span class="text-muted ms-2 small">Track who has voted and who hasn't</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" id="refreshVotersBtn" type="button">
                <i class="bi bi-arrow-repeat me-1"></i>Refresh
            </button>
            <button class="btn btn-sm btn-outline-success" id="exportVotersBtn" type="button">
                <i class="bi bi-download me-1"></i>Export CSV
            </button>
        </div>
    </div>

    <div class="card-body">
        {{-- Statistics Cards --}}
        <div class="row g-3 mb-4" id="voterStats">
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-label text-muted mb-1">Eligible Voters</div>
                    <div class="stat-value text-primary h2 mb-0" id="statEligible">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-label text-muted mb-1">Voted</div>
                    <div class="stat-value text-success h2 mb-0" id="statVoted">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-label text-muted mb-1">Abstained</div>
                    <div class="stat-value text-warning h2 mb-0" id="statAbstained">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3">
                    <div class="stat-label text-muted mb-1">Not Voted</div>
                    <div class="stat-value text-danger h2 mb-0" id="statNotVoted">0</div>
                </div>
            </div>
        </div>

        {{-- Turnout Progress Bar --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between small mb-1">
                <span>Voter Turnout</span>
                <span class="fw-bold" id="turnoutPercentage">0%</span>
            </div>
            <div class="progress" style="height: 8px; border-radius: 4px;">
                <div class="progress-bar bg-success" id="turnoutBar" style="width: 0%; border-radius: 4px;"></div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="voted">✓ Voted</option>
                    <option value="abstained">⚠️ Abstained (No Votes)</option>
                    <option value="not_voted">✗ Not Voted</option>
                </select>
            </div>

            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <input type="text" id="searchVoter" class="form-control" placeholder="Search by name or student ID...">
                    <button class="btn btn-outline-primary" id="searchBtn" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                    <button class="btn btn-outline-secondary" id="clearFiltersBtn" type="button" style="display:none;">
                        <i class="bi bi-x"></i> Clear
                    </button>
                </div>
            </div>

            <div class="col-md-2 text-end">
                <span class="small text-muted" id="voterCount"></span>
            </div>
        </div>

        {{-- Voters Table --}}
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="votersTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%">Student ID</th>
                        <th style="width: 25%">Full Name</th>
                        <th style="width: 15%">Course</th>
                        <th style="width: 10%">Year</th>
                        <th style="width: 15%">Section</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 10%">Voted At</th>
                    </tr>
                </thead>
                <tbody id="votersTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Loading voters...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="small text-muted" id="paginationInfo"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>
</div>

{{-- Voter List Modal (for candidates) --}}
<div class="modal fade" id="votersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-people me-2"></i>
                    <span id="modalCandidateName">Voters</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="badge bg-secondary" id="voteCountBadge">0 votes</span>
                        <span class="badge bg-light text-dark ms-2" id="candidateSectionBadge"></span>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="voterSearchInput" class="form-control form-control-sm" placeholder="Search voters..." style="width: 200px;">
                        <button id="exportCandidateVotersBtn" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Section</th>
                                <th>Year Level</th>
                                <th>Voted At</th>
                            </tr>
                        </thead>
                        <tbody id="votersTableBodyModal">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Loading voters...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="voterPaginationInfo">Showing 0 of 0 voters</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="voterPagination"></ul>
                    </nav>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Abstained Voters Modal --}}
<div class="modal fade" id="abstainModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle me-2"></i>
                    <span id="abstainModalTitle">Abstained Voters</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>What does "abstained" mean?</strong> These voters participated in the election (cast votes for other positions) but chose NOT to vote for <strong id="abstainPositionName"></strong>.
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="badge bg-secondary" id="abstainCountBadge">0 abstained</span>
                        <span class="badge bg-light text-dark ms-2" id="totalParticipantsBadge"></span>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="abstainSearchInput" class="form-control form-control-sm" placeholder="Search by name or ID..." style="width: 250px;">
                        <button id="exportAbstainedBtn" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Section</th>
                                <th>Year Level</th>
                                <th>Voted At</th>
                            </tr>
                        </thead>
                        <tbody id="abstainTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Loading voters...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="abstainPaginationInfo">Showing 0 of 0 voters</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="abstainPagination"></ul>
                    </nav>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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
.badge-status-active    { background: #22c55e20; color: #15803d; }
.badge-status-scheduled { background: #eab30820; color: #a16207; }
.badge-status-paused    { background: #f9731620; color: #9a3412; }
.badge-status-completed { background: #3b82f620; color: #1e40af; }
.badge-status-cancelled { background: #ef444420; color: #991b1b; }
.candidate-card {
    transition: all 0.2s ease;
}
.candidate-card:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.view-abstain-btn:hover {
    text-decoration: underline !important;
    color: #0d6efd !important;
}
.stat-card {
    background: white;
    border-radius: 10px;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = {{ $votingSession->id }};

    // Make variables globally accessible for pagination
    window.currentCandidateId = null;
    window.currentPositionId = null;
    window.currentCandidateName = '';
    window.currentVoterPage = 1;
    window.currentSearchTerm = '';
    window.currentAbstainPage = 1;
    window.currentAbstainSearch = '';

    // Candidate card click handler
    document.querySelectorAll('.candidate-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('a') || e.target.closest('button')) return;

            window.currentCandidateId = this.dataset.candidateId;
            window.currentCandidateName = this.dataset.candidateName;
            const voteCount = this.dataset.voteCount;
            const candidateSection = this.dataset.candidateSection;

            const modalTitle = document.getElementById('modalCandidateName');
            const voteCountBadge = document.getElementById('voteCountBadge');
            const candidateSectionBadge = document.getElementById('candidateSectionBadge');

            if (modalTitle) modalTitle.innerHTML = `Voters for ${window.currentCandidateName}`;
            if (voteCountBadge) voteCountBadge.innerHTML = `${voteCount} votes`;
            if (candidateSectionBadge) candidateSectionBadge.innerHTML = candidateSection;

            window.currentVoterPage = 1;
            window.currentSearchTerm = '';
            const searchInput = document.getElementById('voterSearchInput');
            if (searchInput) searchInput.value = '';

            loadCandidateVoters(window.currentCandidateId, window.currentVoterPage, window.currentSearchTerm);

            const modal = new bootstrap.Modal(document.getElementById('votersModal'));
            modal.show();
        });
    });

    // Abstain button click handler
    document.querySelectorAll('.view-abstain-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.currentPositionId = this.dataset.positionId;
            const positionTitle = this.dataset.positionTitle;
            const abstainCount = this.dataset.abstainCount;
            const totalParticipants = this.dataset.totalParticipants;

            const modalTitle = document.getElementById('abstainModalTitle');
            const abstainCountBadge = document.getElementById('abstainCountBadge');
            const totalParticipantsBadge = document.getElementById('totalParticipantsBadge');
            const abstainPositionName = document.getElementById('abstainPositionName');

            if (modalTitle) modalTitle.innerHTML = `Abstained Voters - ${positionTitle}`;
            if (abstainCountBadge) abstainCountBadge.innerHTML = `${abstainCount} abstained`;
            if (totalParticipantsBadge) totalParticipantsBadge.innerHTML = `out of ${totalParticipants} total participants`;
            if (abstainPositionName) abstainPositionName.innerHTML = `<strong>${positionTitle}</strong>`;

            window.currentAbstainPage = 1;
            window.currentAbstainSearch = '';
            const abstainSearchInput = document.getElementById('abstainSearchInput');
            if (abstainSearchInput) abstainSearchInput.value = '';

            loadAbstainedVoters(window.currentPositionId, window.currentAbstainPage, window.currentAbstainSearch);

            const modal = new bootstrap.Modal(document.getElementById('abstainModal'));
            modal.show();
        });
    });

    // Search input for candidate voters
    const searchInput = document.getElementById('voterSearchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.currentSearchTerm = this.value;
                window.currentVoterPage = 1;
                loadCandidateVoters(window.currentCandidateId, window.currentVoterPage, window.currentSearchTerm);
            }, 500);
        });
    }

    // Search input for abstained voters
    const abstainSearchInput = document.getElementById('abstainSearchInput');
    if (abstainSearchInput) {
        let abstainSearchTimeout;
        abstainSearchInput.addEventListener('input', function() {
            clearTimeout(abstainSearchTimeout);
            abstainSearchTimeout = setTimeout(() => {
                window.currentAbstainSearch = this.value;
                window.currentAbstainPage = 1;
                loadAbstainedVoters(window.currentPositionId, window.currentAbstainPage, window.currentAbstainSearch);
            }, 500);
        });
    }

    // Export candidate voters button
    const exportCandidateBtn = document.getElementById('exportCandidateVotersBtn');
    if (exportCandidateBtn) {
        exportCandidateBtn.addEventListener('click', function() {
            if (!window.currentCandidateId) return;
            let url = `/admin/sessions/{{ $votingSession->id }}/voters/export?candidate_id=${window.currentCandidateId}`;
            if (window.currentSearchTerm) {
                url += `&search=${encodeURIComponent(window.currentSearchTerm)}`;
            }
            window.open(url, '_blank');
        });
    }

    // Export abstained voters button
    const exportAbstainedBtn = document.getElementById('exportAbstainedBtn');
    if (exportAbstainedBtn) {
        exportAbstainedBtn.addEventListener('click', function() {
            if (!window.currentPositionId) return;
            let url = `/admin/sessions/{{ $votingSession->id }}/positions/${window.currentPositionId}/abstained/export`;
            if (window.currentAbstainSearch) {
                url += `?search=${encodeURIComponent(window.currentAbstainSearch)}`;
            }
            window.open(url, '_blank');
        });
    }

    // Voter Tracking System
    let currentPage = 1;
    let currentStatus = '';
    let currentSearch = '';
    let totalPages = 1;

    const votersTableBody = document.getElementById('votersTableBody');
    const statEligible = document.getElementById('statEligible');
    const statVoted = document.getElementById('statVoted');
    const statAbstained = document.getElementById('statAbstained');
    const statNotVoted = document.getElementById('statNotVoted');
    const turnoutPercentage = document.getElementById('turnoutPercentage');
    const turnoutBar = document.getElementById('turnoutBar');
    const voterCountEl = document.getElementById('voterCount');
    const statusFilter = document.getElementById('statusFilter');
    const searchVoter = document.getElementById('searchVoter');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationUl = document.getElementById('pagination');
    const refreshBtn = document.getElementById('refreshVotersBtn');
    const exportBtn = document.getElementById('exportVotersBtn');
    const searchBtn = document.getElementById('searchBtn');
    const clearBtn = document.getElementById('clearFiltersBtn');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '<';
            if (m === '>') return '>';
            return m;
        });
    }

    function updatePagination(current, total) {
        if (!paginationUl) return;

        if (total <= 1) {
            paginationUl.innerHTML = '';
            paginationInfo && (paginationInfo.textContent = '');
            return;
        }

        let html = '';

        if (current > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${current - 1}">« Prev</a></li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">« Prev</span></li>`;
        }

        const startPage = Math.max(1, current - 2);
        const endPage = Math.min(total, current + 2);

        for (let i = startPage; i <= endPage; i++) {
            if (i === current) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }

        if (current < total) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${current + 1}">Next »</a></li>`;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Next »</span></li>`;
        }

        paginationUl.innerHTML = html;

        paginationUl.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(a.getAttribute('data-page'), 10);
                if (Number.isFinite(page)) {
                    currentPage = page;
                    loadVoters();
                }
            });
        });

        paginationInfo && (paginationInfo.textContent = `Page ${current} of ${total}`);
    }

    async function loadVoters() {
        if (!votersTableBody) return;

        votersTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Loading voters...
                </td>
            </tr>
        `;

        const params = new URLSearchParams();
        params.set('page', String(currentPage));
        if (currentStatus) params.set('status', currentStatus);
        if (currentSearch) params.set('search', currentSearch);

        const url = `/admin/sessions/${sessionId}/voters?${params.toString()}`;

        try {
            const res = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                throw new Error(`Request failed: ${res.status}`);
            }

            const response = await res.json();

            if (response.statistics) {
                statEligible && (statEligible.textContent = Number(response.statistics.eligible).toLocaleString());
                statVoted && (statVoted.textContent = Number(response.statistics.voted).toLocaleString());
                statAbstained && (statAbstained.textContent = Number(response.statistics.abstained).toLocaleString());
                statNotVoted && (statNotVoted.textContent = Number(response.statistics.not_voted).toLocaleString());

                if (turnoutPercentage) {
                    const pct = response.statistics.turnout_percentage ?? 0;
                    turnoutPercentage.textContent = `${Number(pct).toFixed(2)}%`;
                }
                if (turnoutBar && response.statistics.turnout_percentage !== undefined) {
                    const pct = Math.max(0, Math.min(100, Number(response.statistics.turnout_percentage)));
                    turnoutBar.style.width = `${pct}%`;
                }
            }

            const voters = response.voters || [];
            const total = response.total ?? 0;

            if (voterCountEl) {
                voterCountEl.textContent = `Showing ${voters.length} of ${total} voters`;
            }

            if (voters.length > 0) {
                let html = '';
                voters.forEach(voter => {
                    let statusBadge = '';
                    switch (voter.status) {
                        case 'voted':
                            statusBadge = `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Voted</span>`;
                            break;
                        case 'abstained':
                            statusBadge = `<span class="badge bg-warning text-dark"><i class="bi bi-eye-slash me-1"></i>Abstained</span>`;
                            break;
                        case 'not_voted':
                        default:
                            statusBadge = `<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Not Voted</span>`;
                            break;
                    }

                    const votedAt = voter.voted_at ? new Date(voter.voted_at).toLocaleString() : '-';

                    html += `
                        <tr>
                            <td><code class="small">${escapeHtml(voter.student_id)}</code></td>
                            <td class="fw-medium">${escapeHtml(voter.full_name)}</td>
                            <td class="small">${escapeHtml(voter.department)}</td>
                            <td class="small">${escapeHtml(voter.year_level)}</td>
                            <td class="small">${escapeHtml(voter.section)}</td>
                            <td>${statusBadge}</td>
                            <td class="small text-muted">${escapeHtml(votedAt)}</td>
                        </tr>
                    `;
                });

                votersTableBody.innerHTML = html;
            } else {
                votersTableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                            No voters found.
                        </td>
                    </tr>
                `;
            }

            const lastPage = response.last_page ?? 1;
            totalPages = lastPage;
            updatePagination(response.current_page ?? 1, lastPage);
        } catch (err) {
            console.error('Error loading voters:', err);
            votersTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Failed to load voters. Please try again.
                    </td>
                </tr>
            `;
        }
    }

    function applyFiltersFromUI() {
        currentStatus = statusFilter ? (statusFilter.value || '') : '';
        currentSearch = searchVoter ? (searchVoter.value || '').trim() : '';
    }

    if (refreshBtn) refreshBtn.addEventListener('click', () => loadVoters());

    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            applyFiltersFromUI();
            currentPage = 1;
            loadVoters();
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            applyFiltersFromUI();
            currentPage = 1;
            loadVoters();
        });
    }

    if (searchVoter) {
        searchVoter.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFiltersFromUI();
                currentPage = 1;
                loadVoters();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (statusFilter) statusFilter.value = '';
            if (searchVoter) searchVoter.value = '';
            currentStatus = '';
            currentSearch = '';
            currentPage = 1;
            clearBtn.style.display = 'none';
            loadVoters();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            applyFiltersFromUI();
            const params = new URLSearchParams();
            if (currentStatus) params.set('status', currentStatus);
            if (currentSearch) params.set('search', currentSearch);
            window.location.href = `/admin/sessions/${sessionId}/voters/export${params.toString() ? '?' + params.toString() : ''}`;
        });
    }

    // Load initial voters
    loadVoters();
});

// Load candidate voters via AJAX
async function loadCandidateVoters(candidateId, page, search) {
    const tbody = document.getElementById('votersTableBodyModal');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Loading voters...
            </td>
        </tr>
    `;

    try {
        let url = `/admin/sessions/{{ $votingSession->id }}/candidates/${candidateId}/voters?page=${page}&per_page=20`;
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            renderVoterTable(data.voters);
            renderVoterPagination(data.current_page, data.last_page, data.total, data.per_page);

            const infoEl = document.getElementById('voterPaginationInfo');
            if (infoEl) {
                const start = ((data.current_page - 1) * data.per_page) + 1;
                const end = Math.min(data.current_page * data.per_page, data.total);
                infoEl.innerHTML = `Showing ${start} to ${end} of ${data.total} voters`;
            }
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-danger">
                        ${data.error || 'Failed to load voters. Please try again.'}
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading voters:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-danger">
                    Error loading voters. Please refresh and try again.
                </td>
            </tr>
        `;
    }
}

// Load abstained voters via AJAX
async function loadAbstainedVoters(positionId, page, search) {
    const tbody = document.getElementById('abstainTableBody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Loading voters...
            </td>
        </tr>
    `;

    try {
        let url = `/admin/sessions/{{ $votingSession->id }}/positions/${positionId}/abstained?page=${page}&per_page=20`;
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            renderAbstainTable(data.voters);
            renderAbstainPagination(data.current_page, data.last_page, data.total, data.per_page);

            const infoEl = document.getElementById('abstainPaginationInfo');
            if (infoEl) {
                const start = ((data.current_page - 1) * data.per_page) + 1;
                const end = Math.min(data.current_page * data.per_page, data.total);
                infoEl.innerHTML = `Showing ${start} to ${end} of ${data.total} voters`;
            }
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-danger">
                        ${data.error || 'Failed to load voters. Please try again.'}
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading abstained voters:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-danger">
                    Error loading voters. Please refresh and try again.
                </td>
            </tr>
        `;
    }
}

function renderVoterTable(voters) {
    const tbody = document.getElementById('votersTableBodyModal');
    if (!tbody) return;

    if (!voters || voters.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    No voters found.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = voters.map(voter => `
        <tr>
            <td><code>${escapeHtml(voter.student_id)}</code></td>
            <td class="fw-medium">${escapeHtml(voter.full_name)}</td>
            <td>${escapeHtml(voter.section)}</td>
            <td>${voter.year_level || 'N/A'}</td>
            <td><small class="text-muted">${voter.voted_at || 'N/A'}</small></td>
        </tr>
    `).join('');
}

function renderAbstainTable(voters) {
    const tbody = document.getElementById('abstainTableBody');
    if (!tbody) return;

    if (!voters || voters.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    No abstained voters found.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = voters.map(voter => `
        <tr>
            <td><code>${escapeHtml(voter.student_id)}</code></td>
            <td class="fw-medium">${escapeHtml(voter.full_name)}</td>
            <td>${escapeHtml(voter.section)}</td>
            <td>${voter.year_level || 'N/A'}</td>
            <td><small class="text-muted">${voter.voted_at || 'N/A'}</small></td>
        </tr>
    `).join('');
}

function renderVoterPagination(currentPage, lastPage, total, perPage) {
    const paginationEl = document.getElementById('voterPagination');
    if (!paginationEl) return;

    if (lastPage <= 1) {
        paginationEl.innerHTML = '';
        return;
    }

    let pages = [];

    pages.push(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
        </li>
    `);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(lastPage, currentPage + 2);

    if (startPage > 1) {
        pages.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
        if (startPage > 2) pages.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }

    if (endPage < lastPage) {
        if (endPage < lastPage - 1) pages.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        pages.push(`<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a></li>`);
    }

    pages.push(`
        <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
        </li>
    `);

    paginationEl.innerHTML = pages.join('');

    paginationEl.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            if (isNaN(page)) return;
            loadCandidateVoters(window.currentCandidateId, page, window.currentSearchTerm);
        });
    });
}

function renderAbstainPagination(currentPage, lastPage, total, perPage) {
    const paginationEl = document.getElementById('abstainPagination');
    if (!paginationEl) return;

    if (lastPage <= 1) {
        paginationEl.innerHTML = '';
        return;
    }

    let pages = [];

    pages.push(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
        </li>
    `);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(lastPage, currentPage + 2);

    if (startPage > 1) {
        pages.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
        if (startPage > 2) pages.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }

    if (endPage < lastPage) {
        if (endPage < lastPage - 1) pages.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        pages.push(`<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a></li>`);
    }

    pages.push(`
        <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
        </li>
    `);

    paginationEl.innerHTML = pages.join('');

    paginationEl.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            if (isNaN(page)) return;
            loadAbstainedVoters(window.currentPositionId, page, window.currentAbstainSearch);
        });
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>
@endsection
