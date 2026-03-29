<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .position-status.done { color: #22c55e; }

        .candidate-label {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem 1.25rem; cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
            position: relative;
        }
        .candidate-label:last-child { border-bottom: none; }
        .candidate-label:hover { background: #f8fafc; }
        .candidate-label input[type="radio"],
        .candidate-label input[type="checkbox"] { display: none; }
        .candidate-label.selected {
            background: #eff6ff;
            border-left: 4px solid #1a56db;
        }
        .candidate-label.selected .check-circle {
            background: #1a56db; border-color: #1a56db;
        }
        .candidate-label.selected .check-circle::after { display: block; }
        .candidate-label.disabled-choice {
            opacity: 0.45; cursor: not-allowed;
        }

        .candidate-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            object-fit: cover; flex-shrink: 0;
            border: 2px solid #e2e8f0; background: #e2e8f0;
        }
        .candidate-label.selected .candidate-avatar { border-color: #1a56db; }

        .check-circle {
            width: 22px; height: 22px; border-radius: 50%;
            border: 2px solid #cbd5e1; margin-left: auto; flex-shrink: 0;
            position: relative;
        }
        .check-circle::after {
            content: ''; display: none;
            width: 10px; height: 10px; border-radius: 50%;
            background: #fff;
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Square check for multi-winner */
        .check-square {
            width: 22px; height: 22px; border-radius: 4px;
            border: 2px solid #cbd5e1; margin-left: auto; flex-shrink: 0;
            position: relative;
        }
        .candidate-label.selected .check-square {
            background: #1a56db; border-color: #1a56db;
        }
        .check-square::after {
            content: '✓'; display: none;
            color: #fff; font-size: 13px; font-weight: 700;
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }
        .candidate-label.selected .check-square::after { display: block; }

        .multi-hint {
            background: #fffbeb; border: 1px solid #fcd34d;
            border-radius: 8px; padding: 0.5rem 1rem;
            font-size: 0.78rem; color: #92400e;
            margin: 0.5rem 1.25rem;
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
    </style>
</head>
<body>

<div class="ballot-header">
    <div class="container" style="max-width:700px">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5>{{ $votingSession->title }}</h5>
                <small>{{ $votingSession->positions->count() }} position(s)</small>
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
            <span id="progressText">0</span> of {{ $votingSession->positions->count() }} positions selected
        </div>
    </div>
</div>

<div class="container py-4" style="max-width:700px">

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($alreadyVoted && $votingSession->allow_vote_changes)
    <div class="alert alert-warning py-2 mb-4">
        <i class="bi bi-info-circle me-1"></i>You have already voted. Submitting will replace your previous vote.
    </div>
    @endif

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
        <div class="position-card" id="posCard{{ $position->id }}">
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
                You may select <strong>up to {{ $position->max_winners }} candidates</strong> for this position.
            </div>
            @endif

            @foreach($position->candidates as $candidate)
            <label class="candidate-label" id="label-{{ $position->id }}-{{ $candidate->id }}">
                @if($position->max_winners > 1)
                    <input type="checkbox"
                           name="votes[{{ $position->id }}][]"
                           value="{{ $candidate->id }}"
                           data-position="{{ $position->id }}"
                           data-max="{{ $position->max_winners }}">
                @else
                    <input type="radio"
                           name="votes[{{ $position->id }}]"
                           value="{{ $candidate->id }}"
                           data-position="{{ $position->id }}"
                           required>
                @endif

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

                @if($position->max_winners > 1)
                    <div class="check-square"></div>
                @else
                    <div class="check-circle"></div>
                @endif
            </label>
            @endforeach

            @if($position->candidates->count() === 0)
            <div class="p-3 text-muted small text-center">No candidates for this position.</div>
            @endif
        </div>
        @endforeach

        {{-- Submit --}}
        <div class="d-flex gap-3 mt-4">
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary px-4">Cancel</a>
            <button type="submit" class="btn-submit" id="submitBtn" disabled>
                Submit My Votes →
            </button>
        </div>

        <p class="text-center text-muted small mt-3">
            <i class="bi bi-lock me-1"></i>Your vote is anonymous and securely recorded.
        </p>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const totalPositions = {{ $votingSession->positions->count() }};
    const selected = {};

    // Handle RADIO buttons (single winner)
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const posId  = this.dataset.position;
            const candId = this.value;

            document.querySelectorAll(`label[id^="label-${posId}-"]`).forEach(l => l.classList.remove('selected'));
            document.getElementById(`label-${posId}-${candId}`).classList.add('selected');

            selected[posId] = [candId];
            updateProgress(posId);
        });
    });

    // Handle CHECKBOX buttons (multiple winners)
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const posId = this.dataset.position;
            const max   = parseInt(this.dataset.max);
            const label = document.getElementById(`label-${posId}-${this.value}`);
            const allCheckboxes = document.querySelectorAll(`input[type="checkbox"][data-position="${posId}"]`);
            const checked = document.querySelectorAll(`input[type="checkbox"][data-position="${posId}"]:checked`);

            if (this.checked && checked.length > max) {
                this.checked = false;
                return;
            }

            label.classList.toggle('selected', this.checked);

            const rechecked = document.querySelectorAll(`input[type="checkbox"][data-position="${posId}"]:checked`);

            // Disable unchosen ones if max reached
            allCheckboxes.forEach(cb => {
                const cbLabel = document.getElementById(`label-${posId}-${cb.value}`);
                if (!cb.checked) {
                    cb.disabled = rechecked.length >= max;
                    cbLabel.classList.toggle('disabled-choice', rechecked.length >= max);
                } else {
                    cb.disabled = false;
                    cbLabel.classList.remove('disabled-choice');
                }
            });

            selected[posId] = Array.from(rechecked).map(c => c.value);
            if (selected[posId].length === 0) delete selected[posId];

            updateProgress(posId);
        });
    });

    function updateProgress(posId) {
        const statusEl = document.getElementById(`posStatus${posId}`);
        if (selected[posId] && selected[posId].length > 0) {
            statusEl.textContent = `✓ ${selected[posId].length} selected`;
            statusEl.classList.add('done');
        } else {
            statusEl.textContent = 'Not selected';
            statusEl.classList.remove('done');
        }

        const count = Object.keys(selected).length;
        document.getElementById('progressText').textContent = count;
        document.getElementById('progressFill').style.width = ((count / totalPositions) * 100) + '%';
        document.getElementById('submitBtn').disabled = count < totalPositions;
    }
</script>
</body>
</html>
