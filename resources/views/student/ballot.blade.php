<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — Ballot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; font-family: 'Segoe UI', system-ui, sans-serif; }

        .ballot-header {
            background: #1a56db; color: #fff;
            padding: 1.25rem 2rem;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 10px rgba(26,86,219,0.3);
        }
        .ballot-header h5 { margin: 0; font-weight: 700; }
        .ballot-header small { opacity: 0.7; font-size: 0.82rem; }

        .progress-wrap {
            background: rgba(255,255,255,0.2);
            border-radius: 4px; height: 4px; overflow: hidden; margin-top: 0.5rem;
        }
        .progress-fill {
            background: #93c5fd; height: 100%; border-radius: 4px;
            transition: width 0.3s ease;
        }

        .position-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden;
            transition: all 0.3s;
        }
        .position-card.skipped {
            opacity: 0.7;
            background: #f8fafc;
        }
        .position-card.skipped .position-header {
            background: #f1f5f9;
        }

        .position-header {
            background: #f8fafc; padding: 0.9rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .position-number {
            width: 26px; height: 26px; border-radius: 50%;
            background: #1a56db; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
        }
        .position-status {
            margin-left: auto; font-size: 0.75rem; font-weight: 500; color: #94a3b8;
        }
        .position-status.voted { color: #22c55e; }
        .position-status.skipped { color: #f59e0b; }

        .candidate-label {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem 1.25rem; cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
            position: relative;
        }
        .candidate-label:last-child { border-bottom: none; }
        .candidate-label:hover { background: #f8fafc; }
        .candidate-label input[type="checkbox"] { display: none; }
        .candidate-label.selected {
            background: #eff6ff;
            border-left: 4px solid #1a56db;
        }
        .candidate-label.selected .check-square {
            background: #1a56db; border-color: #1a56db;
        }
        .candidate-label.selected .check-square::after { display: block; }
        .candidate-label.disabled-choice {
            opacity: 0.45; cursor: not-allowed;
        }

        .candidate-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            object-fit: cover; flex-shrink: 0;
            border: 2px solid #e2e8f0; background: #e2e8f0;
        }
        .candidate-label.selected .candidate-avatar { border-color: #1a56db; }

        .check-square {
            width: 22px; height: 22px; border-radius: 4px;
            border: 2px solid #cbd5e1; margin-left: auto; flex-shrink: 0;
            position: relative;
        }
        .check-square::after {
            content: '✓'; display: none;
            color: #fff; font-size: 13px; font-weight: 700;
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }

        .multi-hint {
            background: #fffbeb; border: 1px solid #fcd34d;
            border-radius: 8px; padding: 0.5rem 1rem;
            font-size: 0.78rem; color: #92400e;
            margin: 0.5rem 1.25rem;
        }

        .skip-option {
            background: #fef9e3;
            border-top: 1px solid #fde68a;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background 0.15s;
        }
        .skip-option:hover {
            background: #fef3c7;
        }
        .skip-option.selected {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .skip-checkbox {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid #f59e0b;
            margin-left: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .skip-option.selected .skip-checkbox {
            background: #f59e0b;
        }
        .skip-option.selected .skip-checkbox::after {
            content: '✓';
            color: #fff;
            font-size: 12px;
        }

        .release-card {
            background: #fff; border-radius: 14px;
            border: 2px solid #fbbf24;
            padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
        }

        .btn-submit {
            background: #1a56db; color: #fff; border: none;
            border-radius: 12px; padding: 1rem; font-size: 1.05rem;
            font-weight: 700; width: 100%; transition: all 0.2s;
        }
        .btn-submit:hover:not(:disabled) { background: #1447c0; transform: translateY(-1px); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

        .alert {
            border-radius: 12px;
        }

        .summary-bar {
            background: white;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
        }

        .summary-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 1rem;
            font-size: 0.85rem;
        }

        .summary-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .summary-badge.voted {
            background: #22c55e20;
            color: #15803d;
        }

        .summary-badge.skipped {
            background: #f59e0b20;
            color: #b45309;
        }

        .instruction-note {
            background: #e0e7ff;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instruction-note i {
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="ballot-header">
    <div class="container" style="max-width:800px">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5>{{ $votingSession->title }}</h5>
                <small>{{ $votingSession->positions->count() }} position(s) · You may skip positions you don't wish to vote for</small>
            </div>
            <a href="{{ route('student.dashboard') }}"
               style="color:rgba(255,255,255,0.7);font-size:0.85rem;text-decoration:none">
                ✕ Cancel
            </a>
        </div>
        <div class="progress-wrap mt-2">
            <div class="progress-fill" id="progressFill" style="width:0%"></div>
        </div>
        <div style="font-size:0.75rem;opacity:0.6;margin-top:3px">
            <span id="progressText">0</span> of {{ $votingSession->positions->count() }} positions reviewed
        </div>
    </div>
</div>

<div class="container py-4" style="max-width:800px">

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($alreadyVoted && $votingSession->allow_vote_changes)
    <div class="alert alert-warning py-2 mb-4">
        <i class="bi bi-info-circle me-1"></i>You have already voted. Submitting will replace your previous vote.
    </div>
    @endif

    {{-- Instruction Note --}}
    <div class="instruction-note">
        <i class="bi bi-info-circle-fill"></i>
        <span>Select candidates you want to vote for. Leave all unchecked to skip a position. Each position requires either a vote or skip to proceed.</span>
    </div>

    {{-- Summary Bar --}}
    <div class="summary-bar">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="summary-item">
                    <span class="summary-badge voted" id="votedCountBadge">0</span>
                    <span>Positions voted</span>
                </span>
                <span class="summary-item">
                    <span class="summary-badge skipped" id="skippedCountBadge">0</span>
                    <span>Positions skipped</span>
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('student.vote', $votingSession) }}" id="ballotForm">
        @csrf

        {{-- Release code --}}
        @if($votingSession->requires_release_code)
        <div class="release-card">
            <label class="form-label fw-bold small text-warning-emphasis">
                <i class="bi bi-key-fill me-1"></i>Release Code Required
            </label>
            <input type="text" name="release_code"
                   class="form-control @error('release_code') is-invalid @enderror"
                   placeholder="Enter your release code" required>
            @error('release_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endif

        {{-- Positions --}}
        @foreach($votingSession->positions as $index => $position)
        <div class="position-card" id="posCard{{ $position->id }}" data-position-id="{{ $position->id }}">
            <div class="position-header">
                <div class="position-number">{{ $index + 1 }}</div>
                {{ $position->title }}
                @if($position->max_winners > 1)
                    <span style="font-size:0.72rem;color:#64748b;font-weight:400">(Select up to {{ $position->max_winners }})</span>
                @endif
                <span class="position-status" id="posStatus{{ $position->id }}">Not selected</span>
            </div>

            @if($position->max_winners > 1)
            <div class="multi-hint">
                <i class="bi bi-info-circle me-1"></i>
                You may select <strong>up to {{ $position->max_winners }} candidates</strong>. Leave all unchecked to skip this position.
            </div>
            @endif

            @foreach($position->candidates as $candidate)
            <label class="candidate-label" id="label-{{ $position->id }}-{{ $candidate->id }}" data-position-id="{{ $position->id }}" data-candidate-id="{{ $candidate->id }}">
                <input type="checkbox"
                       name="votes[{{ $position->id }}][]"
                       value="{{ $candidate->id }}"
                       data-position="{{ $position->id }}"
                       data-max="{{ $position->max_winners }}"
                       class="candidate-checkbox">

                <img src="{{ $candidate->photo_url }}"
                     class="candidate-avatar" alt="{{ $candidate->student->full_name }}">
                <div class="flex-grow-1">
                    <div style="font-weight:600;color:#1e293b">{{ $candidate->student->full_name }}</div>
                    <div style="font-size:0.82rem;color:#64748b">
                        {{ $candidate->student->section }}
                    </div>
                    @if($candidate->manifesto)
                    <div style="font-size:0.78rem;color:#94a3b8;margin-top:2px">
                        {{ Str::limit($candidate->manifesto, 100) }}
                    </div>
                    @endif
                </div>

                <div class="check-square"></div>
            </label>
            @endforeach

            {{-- Skip option for this position --}}
            <div class="skip-option" id="skipOption{{ $position->id }}" onclick="toggleSkipPosition({{ $position->id }})">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-eye-slash text-warning"></i>
                    <span style="font-size:0.85rem;">Abstain from voting for this position</span>
                </div>
                <div class="skip-checkbox" id="skipCheckbox{{ $position->id }}"></div>
            </div>

            @if($position->candidates->count() === 0)
            <div class="p-3 text-muted small text-center">No candidates for this position. You may skip this position.</div>
            @endif
        </div>
        @endforeach

        {{-- Submit --}}
        <div class="d-flex gap-3 mt-4">
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary px-4">Cancel</a>
            <button type="submit" class="btn-submit" id="submitBtn">
                Submit My Votes →
            </button>
        </div>

        <p class="text-center text-muted small mt-3">
            <i class="bi bi-lock me-1"></i>Your vote is anonymous and securely recorded.
            <br>You may abstain from any position by checking the skip option. Each position requires a decision.
        </p>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const totalPositions = {{ $votingSession->positions->count() }};
        let selections = {};
        let skippedPositions = {};

        // Helper function to update all UI elements
        function updateProgress() {
            const votedCount = Object.keys(selections).filter(posId => selections[posId] && selections[posId].length > 0).length;
            const skippedCount = Object.keys(skippedPositions).filter(posId => skippedPositions[posId] === true).length;
            const reviewedCount = votedCount + skippedCount;

            // Update progress bar
            document.getElementById('progressText').textContent = reviewedCount;
            document.getElementById('progressFill').style.width = ((reviewedCount / totalPositions) * 100) + '%';

            // Update summary badges
            document.getElementById('votedCountBadge').textContent = votedCount;
            document.getElementById('skippedCountBadge').textContent = skippedCount;

            // Enable submit button if all positions are either voted or skipped
            document.getElementById('submitBtn').disabled = reviewedCount < totalPositions;

            // Update each position's status
            for (let posId = 1; posId <= totalPositions; posId++) {
                updatePositionStatus(posId);
            }
        }

        function updatePositionStatus(posId) {
            const statusEl = document.getElementById(`posStatus${posId}`);
            const card = document.getElementById(`posCard${posId}`);

            if (selections[posId] && selections[posId].length > 0) {
                statusEl.textContent = `✓ ${selections[posId].length} selected`;
                statusEl.classList.add('voted');
                statusEl.classList.remove('skipped');
                card.classList.remove('skipped');
            } else if (skippedPositions[posId]) {
                statusEl.textContent = '⨯ Skipped';
                statusEl.classList.add('skipped');
                statusEl.classList.remove('voted');
                card.classList.add('skipped');
            } else {
                statusEl.textContent = 'Not selected';
                statusEl.classList.remove('voted', 'skipped');
                card.classList.remove('skipped');
            }
        }

        // Handle checkbox changes for candidates
        document.querySelectorAll('.candidate-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const posId = this.dataset.position;
                const max = parseInt(this.dataset.max);
                const candidateId = this.value;
                const label = document.getElementById(`label-${posId}-${candidateId}`);
                const allCheckboxes = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]`);
                const checked = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]:checked`);

                // Check if trying to select more than max
                if (this.checked && checked.length > max) {
                    this.checked = false;
                    alert(`You can only select up to ${max} candidates for this position.`);
                    return;
                }

                // Toggle selected class on label
                if (this.checked) {
                    label.classList.add('selected');
                } else {
                    label.classList.remove('selected');
                }

                const rechecked = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]:checked`);

                // Enable/disable other checkboxes based on max limit
                allCheckboxes.forEach(cb => {
                    const cbLabel = document.getElementById(`label-${posId}-${cb.value}`);
                    if (!cb.checked) {
                        if (rechecked.length >= max) {
                            cb.disabled = true;
                            cbLabel.classList.add('disabled-choice');
                        } else {
                            cb.disabled = false;
                            cbLabel.classList.remove('disabled-choice');
                        }
                    } else {
                        cb.disabled = false;
                        cbLabel.classList.remove('disabled-choice');
                    }
                });

                // Update selections
                if (rechecked.length > 0) {
                    selections[posId] = Array.from(rechecked).map(cb => cb.value);
                } else {
                    delete selections[posId];
                }

                // If we have any votes for this position, remove skip status
                if (selections[posId] && selections[posId].length > 0) {
                    skippedPositions[posId] = false;
                    const skipOption = document.getElementById(`skipOption${posId}`);
                    const skipCheckbox = document.getElementById(`skipCheckbox${posId}`);
                    if (skipOption) skipOption.classList.remove('selected');
                    if (skipCheckbox) skipCheckbox.classList.remove('selected');
                }

                updateProgress();
            });
        });

        // Toggle skip position
        window.toggleSkipPosition = function(posId) {
            const skipOption = document.getElementById(`skipOption${posId}`);
            const skipCheckbox = document.getElementById(`skipCheckbox${posId}`);
            const positionCard = document.getElementById(`posCard${posId}`);

            // If we have any votes for this position, clear them first
            if (selections[posId] && selections[posId].length > 0) {
                if (!confirm('You have selected candidates for this position. Skipping will clear your selections. Continue?')) {
                    return;
                }

                // Clear all checkboxes for this position
                const checkboxes = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]`);
                checkboxes.forEach(cb => {
                    cb.checked = false;
                    const label = document.getElementById(`label-${posId}-${cb.value}`);
                    if (label) label.classList.remove('selected');
                    cb.disabled = false;
                });
                delete selections[posId];
            }

            // Toggle skip status
            if (skippedPositions[posId]) {
                // Un-skip
                skippedPositions[posId] = false;
                skipOption.classList.remove('selected');
                skipCheckbox.classList.remove('selected');
                positionCard.classList.remove('skipped');
            } else {
                // Skip
                skippedPositions[posId] = true;
                skipOption.classList.add('selected');
                skipCheckbox.classList.add('selected');
                positionCard.classList.add('skipped');
            }

            updateProgress();
        };

        // Initialize any pre-selected values
        function initializeSelections() {
            document.querySelectorAll('.candidate-checkbox:checked').forEach(checkbox => {
                const posId = checkbox.dataset.position;
                const candidateId = checkbox.value;

                if (!selections[posId]) selections[posId] = [];
                if (!selections[posId].includes(candidateId)) {
                    selections[posId].push(candidateId);
                }

                const label = document.getElementById(`label-${posId}-${candidateId}`);
                if (label) label.classList.add('selected');
            });

            updateProgress();
        }

        initializeSelections();

        // Form submission validation
        document.getElementById('ballotForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const reviewedCount = parseInt(document.getElementById('progressText').textContent);
            const totalPositions = {{ $votingSession->positions->count() }};

            if (reviewedCount < totalPositions) {
                e.preventDefault();
                alert('Please either vote or skip all positions before submitting.');
                return false;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting...';
        });
    });
</script>
</body>
</html>
