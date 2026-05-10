@extends('layouts.admin')
@section('title', $votingSession->title)

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        {{-- Real-time status badge — driven by JS countdown, not a static server value --}}
        <span id="statusBadge"
              class="badge px-3 py-2 mb-2"
              style="border-radius:8px;font-size:0.85rem"
              data-start="{{ $votingSession->start_date->timestamp }}"
              data-end="{{ $votingSession->end_date->timestamp }}"
              data-server-status="{{ $votingSession->status }}">
            {{ ucfirst($votingSession->status) }}
        </span>

        {{-- Countdown / time context line --}}
        <div class="text-muted small mb-1" id="timeContext"></div>

        <p class="text-muted mb-0 small">
            <span id="scheduleDisplay">
                {{ $votingSession->start_date->format('M d, Y H:i') }} →
                {{ $votingSession->end_date->format('M d, Y H:i') }}
            </span>
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
            @if($votingSession->requires_release_code)
                &nbsp;·&nbsp; <span class="badge bg-warning text-dark border" style="font-size:0.8rem">
                    <i class="bi bi-qr-code me-1"></i>Release Code Required
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
    </div>
</div>

{{--
    ── Schedule Editor ────────────────────────────────────────────────────────
    Replaces the old "Change Status" card. Status is now derived automatically
    from the start/end times. Admins can only manually override to
    paused or cancelled (states that can't be inferred from time).
--}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-calendar-range me-2 text-primary"></i>Adjust Schedule</strong>
        <button class="btn btn-sm btn-outline-primary" id="editScheduleBtn" onclick="toggleScheduleEdit()">
            <i class="bi bi-pencil me-1"></i>Edit
        </button>
    </div>
    <div class="card-body">

        {{-- Read-only view --}}
        <div id="scheduleReadView">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded bg-light border">
                        <div class="small text-muted mb-1"><i class="bi bi-play-fill text-success me-1"></i>Starts</div>
                        <div class="fw-semibold">{{ $votingSession->start_date->format('M d, Y — h:i A') }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded bg-light border">
                        <div class="small text-muted mb-1"><i class="bi bi-stop-fill text-danger me-1"></i>Ends</div>
                        <div class="fw-semibold">{{ $votingSession->end_date->format('M d, Y — h:i A') }}</div>
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex align-items-center gap-2 small text-muted">
                <i class="bi bi-info-circle"></i>
                Status updates automatically: <strong>Scheduled → Active → Completed</strong> based on these times.
                Use the override below only if you need to pause or cancel.
            </div>
        </div>

        {{-- Edit form --}}
        <div id="scheduleEditView" class="d-none">
            <form method="POST" action="{{ route('admin.sessions.reschedule', $votingSession) }}">
                @csrf
                @method('PATCH')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            <i class="bi bi-play-fill text-success me-1"></i>Start Date & Time
                        </label>
                        <input type="datetime-local" name="start_date" class="form-control"
                               value="{{ $votingSession->start_date->format('Y-m-d\TH:i') }}"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            <i class="bi bi-stop-fill text-danger me-1"></i>End Date & Time
                        </label>
                        <input type="datetime-local" name="end_date" class="form-control"
                               value="{{ $votingSession->end_date->format('Y-m-d\TH:i') }}"
                               required>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Save Schedule
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleScheduleEdit()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        {{-- Manual override (paused / cancelled only) --}}
        @if(!in_array($votingSession->status, ['completed', 'cancelled']))
        <hr class="my-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-semibold">Manual override:</span>
            <form method="POST" action="{{ route('admin.sessions.status', $votingSession) }}" class="d-inline">
                @csrf
                @if($votingSession->status === 'paused')
                    <button type="submit" name="status" value="scheduled"
                            class="btn btn-sm btn-outline-success">
                        <i class="bi bi-play me-1"></i>Resume (restore schedule)
                    </button>
                @else
                    <button type="submit" name="status" value="paused"
                            class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pause me-1"></i>Pause
                    </button>
                @endif
                <button type="submit" name="status" value="cancelled"
                        class="btn btn-sm btn-outline-danger ms-1"
                        onclick="return confirm('Are you sure you want to cancel this election? This cannot be easily undone.')">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
            </form>
        </div>
        @elseif($votingSession->status === 'cancelled')
        <hr class="my-3">
        <form method="POST" action="{{ route('admin.sessions.status', $votingSession) }}" class="d-inline">
            @csrf
            <button type="submit" name="status" value="scheduled"
                    class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reinstate (set back to Scheduled)
            </button>
        </form>
        @endif

    </div>
</div>

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

@if($votingSession->requires_release_code && $votingSession->releaseCodes->count())
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-qr-code me-2 text-primary"></i>Release Codes & QR Codes</strong>
        <span class="badge bg-primary ms-2">{{ $votingSession->releaseCodes->count() }} codes</span>
    </div>
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Students can either type the code OR scan the QR code to access the voting ballot.
        </div>
        <div class="row g-4">
            @foreach($votingSession->releaseCodes as $code)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-body text-center">
                        <!-- QR Code -->
                        <div class="mb-3 p-3 bg-white rounded d-flex justify-content-center"
                             data-qr-code="{{ $code->code }}"
                             style="background: #f8fafc; min-height: 200px;">
                            {!! QrCode::size(160)->errorCorrection('H')->generate($code->code) !!}
                        </div>

                        <!-- Code Display -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                                <code class="fw-bold fs-4 bg-light px-3 py-2 rounded" style="letter-spacing: 2px;">{{ $code->code }}</code>
                                <button class="btn btn-sm btn-outline-primary copy-code-btn" data-code="{{ $code->code }}" title="Copy Code">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Code Details -->
                        <div class="small text-muted">
                            @if($code->description)
                                <div><i class="bi bi-tag"></i> {{ $code->description }}</div>
                            @endif
                            @if($code->expires_at)
                                <div><i class="bi bi-clock"></i> Expires: {{ $code->expires_at->format('M d, Y') }}</div>
                            @else
                                <div><i class="bi bi-infinity"></i> No expiry</div>
                            @endif
                            <div class="mt-2">
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>

                        <!-- Download Button -->
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-success qr-download-btn"
                                    data-code="{{ $code->code }}"
                                    data-title="{{ $votingSession->title }}">
                                <i class="bi bi-download"></i> Download PNG
                            </button>

                            {{-- Hidden card used as canvas source --}}
                            <div class="qr-card-template d-none"
                                 id="qr-card-{{ $code->code }}"
                                 data-code="{{ $code->code }}"
                                 data-title="{{ $votingSession->title }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

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
.badge-status-active    { background: #22c55e20; color: #15803d; }
.badge-status-scheduled { background: #eab30820; color: #a16207; }
.badge-status-paused    { background: #f9731620; color: #9a3412; }
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
#timeContext {
    font-size: 0.78rem;
    min-height: 1.1em;
}
</style>

@endsection

@push('scripts')
<script>
// ── Copy-code buttons ──────────────────────────────────────────────────────
document.querySelectorAll('.copy-code-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const code = this.dataset.code;
        navigator.clipboard.writeText(code);
        const originalIcon = this.innerHTML;
        this.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
        setTimeout(() => { this.innerHTML = originalIcon; }, 2000);
    });
});

// ── Schedule edit toggle ───────────────────────────────────────────────────
function toggleScheduleEdit() {
    const readView = document.getElementById('scheduleReadView');
    const editView = document.getElementById('scheduleEditView');
    const btn      = document.getElementById('editScheduleBtn');
    const isEditing = !editView.classList.contains('d-none');

    if (isEditing) {
        editView.classList.add('d-none');
        readView.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-pencil me-1"></i>Edit';
        btn.classList.replace('btn-primary', 'btn-outline-primary');
    } else {
        readView.classList.add('d-none');
        editView.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-x me-1"></i>Cancel';
        btn.classList.replace('btn-outline-primary', 'btn-primary');
    }
}

// ── Real-time status badge ─────────────────────────────────────────────────
(function () {
    const badge        = document.getElementById('statusBadge');
    const timeCtx      = document.getElementById('timeContext');
    const startTs      = parseInt(badge.dataset.start, 10) * 1000;   // ms
    const endTs        = parseInt(badge.dataset.end,   10) * 1000;
    const serverStatus = badge.dataset.serverStatus;

    // Statuses that are manual-only — never override with time
    const MANUAL_ONLY = ['paused', 'cancelled'];

    function formatDuration(ms) {
        if (ms <= 0) return '0s';
        const totalSec = Math.floor(ms / 1000);
        const days  = Math.floor(totalSec / 86400);
        const hours = Math.floor((totalSec % 86400) / 3600);
        const mins  = Math.floor((totalSec % 3600)  / 60);
        const secs  = totalSec % 60;

        if (days > 1)  return `${days}d ${hours}h`;
        if (days === 1) return `1d ${hours}h ${mins}m`;
        if (hours > 0) return `${hours}h ${mins}m`;
        if (mins  > 0) return `${mins}m ${secs}s`;
        return `${secs}s`;
    }

    function applyBadge(status, label) {
        // Strip all status classes first
        badge.className = badge.className
            .replace(/badge-status-\S+/g, '')
            .trim();
        badge.classList.add(`badge-status-${status}`);
        badge.textContent = label;
    }

    function tick() {
        const now = Date.now();

        // If admin forced a manual state, just show it — don't override
        if (MANUAL_ONLY.includes(serverStatus)) {
            applyBadge(serverStatus, serverStatus.charAt(0).toUpperCase() + serverStatus.slice(1));
            timeCtx.textContent = serverStatus === 'paused'
                ? '⏸ Election is paused by an administrator.'
                : '✕ Election has been cancelled.';
            return; // no further ticking needed
        }

        if (now < startTs) {
            // Upcoming
            const diff = startTs - now;
            applyBadge('scheduled', 'Scheduled');
            timeCtx.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>Starts in <strong>${formatDuration(diff)}</strong>`;
        } else if (now >= startTs && now <= endTs) {
            // Live
            const diff = endTs - now;
            applyBadge('active', '🟢 Active');
            timeCtx.innerHTML = `<i class="bi bi-clock me-1"></i>Ends in <strong>${formatDuration(diff)}</strong>`;
        } else {
            // Over
            applyBadge('completed', 'Completed');
            const diff = now - endTs;
            timeCtx.innerHTML = `<i class="bi bi-check-circle me-1 text-primary"></i>Ended <strong>${formatDuration(diff)}</strong> ago`;
            clearInterval(timer); // no need to keep ticking
        }
    }

    tick(); // immediate first render
    const timer = setInterval(tick, 1000);
})();

// ── QR Download helper ─────────────────────────────────────────────────────
function buildQRCard(code, title) {
    const card = document.createElement('div');
    card.style.cssText = `
        width: 340px; padding: 28px 24px 24px;
        background: white; border-radius: 20px;
        border: 2px solid #1a56db;
        font-family: Arial, sans-serif;
        text-align: center;
        position: fixed; left: -9999px; top: 0;
    `;

    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg,#1a56db,#7c3aed); color:white; padding:10px 16px; border-radius:10px; margin-bottom:16px; font-weight:700; font-size:13px; letter-spacing:0.5px;';
    header.textContent = 'VoteCast — Election Access';
    card.appendChild(header);

    const titleEl = document.createElement('div');
    titleEl.style.cssText = 'font-size:14px; color:#374151; font-weight:600; margin-bottom:14px; line-height:1.4;';
    titleEl.textContent = title || '';
    card.appendChild(titleEl);

    const qrWrap = document.createElement('div');
    qrWrap.style.cssText = 'display:flex; justify-content:center; margin-bottom:14px;';

    const liveSvg = document.querySelector(`[data-qr-code="${code}"] svg`);
    if (liveSvg) {
        const clone = liveSvg.cloneNode(true);
        clone.setAttribute('width', '200');
        clone.setAttribute('height', '200');
        qrWrap.appendChild(clone);
    }
    card.appendChild(qrWrap);

    const codeEl = document.createElement('div');
    codeEl.style.cssText = 'font-size:28px; font-weight:800; letter-spacing:6px; font-family:monospace; background:#f0f4ff; padding:12px 20px; border-radius:10px; color:#1a56db; margin-bottom:12px;';
    codeEl.textContent = code;
    card.appendChild(codeEl);

    const hint = document.createElement('div');
    hint.style.cssText = 'font-size:11px; color:#6b7280;';
    hint.textContent = 'Scan QR or enter code to vote';
    card.appendChild(hint);

    document.body.appendChild(card);
    return card;
}

document.querySelectorAll('.qr-download-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const code  = this.dataset.code;
        const title = this.dataset.title;

        const origHtml = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating…';
        this.disabled  = true;

        const card = buildQRCard(code, title);
        try {
            const canvas = await html2canvas(card, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true
            });

            const link = document.createElement('a');
            link.download = `qr-${code}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } finally {
            document.body.removeChild(card);
            this.innerHTML = origHtml;
            this.disabled  = false;
        }
    });
});

// ── Live vote monitor (unchanged) ─────────────────────────────────────────
class VoteMonitor {
    constructor(sessionId) {
        this.sessionId = sessionId;
        this.pollingInterval = null;
        this.isRefreshing = false;
        this.init();
    }

    init() {
        this.startPolling();
        this.pollingInterval = setInterval(() => this.fetchVotes(), 3000);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (this.pollingInterval) { clearInterval(this.pollingInterval); this.pollingInterval = null; }
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

    startPolling() { this.fetchVotes(); }

    stop() {
        if (this.pollingInterval) { clearInterval(this.pollingInterval); this.pollingInterval = null; }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const voteMonitor = new VoteMonitor({{ $votingSession->id }});
    window.addEventListener('beforeunload', () => { if (voteMonitor) voteMonitor.stop(); });
});
</script>
@endpush
