<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VoteCast — Ballot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modalFadeIn {
            from { opacity: 0; backdrop-filter: blur(0px); }
            to { opacity: 1; backdrop-filter: blur(4px); }
        }

        /* ── Scroll container ── */
        .candidates-scroll-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            padding: 1.25rem;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
            overscroll-behavior-x: contain;
        }

        /* ── Cards — flexible on desktop ── */
        .candidate-card {
            animation: fadeIn 0.2s ease-out;
            cursor: pointer;
            flex: 1 1 240px;
            max-width: 300px;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            user-select: none;
        }

        .candidate-card:hover {
            transform: translateY(-4px);
        }
        .candidate-card:active {
            transform: scale(0.98);
        }

        /* ── Mobile — single row horizontal scroll ── */
        @media (max-width: 768px) {
            .candidates-scroll-container {
                flex-wrap: nowrap;
                -webkit-mask-image: linear-gradient(to right, black 85%, transparent 100%);
                mask-image: linear-gradient(to right, black 85%, transparent 100%);
            }

            .candidates-scroll-container.at-end {
                -webkit-mask-image: none;
                mask-image: none;
            }

            .candidate-card {
                flex: 0 0 260px;
                max-width: 82vw;
            }
        }

        /* Scrollbar — only visible/useful on mobile */
        .candidates-scroll-container::-webkit-scrollbar {
            height: 5px;
        }
        .candidates-scroll-container::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
            margin: 0 1.25rem;
        }
        .candidates-scroll-container::-webkit-scrollbar-thumb {
            background: #1a56db;
            border-radius: 10px;
        }
        .candidates-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #1447c0;
        }

        .skip-checkbox {
            transition: all 0.2s;
        }

        .skip-option-area {
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Modal overlay - blur effect */
        .code-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: modalFadeIn 0.3s ease-out;
        }

        .code-modal {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 32px;
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .code-modal .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1a56db 0%, #7c3aed 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 25px -5px rgba(26, 86, 219, 0.3);
        }

        .code-modal .modal-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .code-input-custom {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem;
            font-size: 1.25rem;
            letter-spacing: 6px;
            font-family: 'Courier New', monospace;
            text-align: center;
            font-weight: 700;
            transition: all 0.2s;
        }

        .code-input-custom:focus {
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.2);
            outline: none;
        }

        .code-input-custom.is-invalid {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .btn-verify {
            background: linear-gradient(135deg, #1a56db 0%, #1447c0 100%);
            border: none;
            border-radius: 16px;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.2s;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(26, 86, 219, 0.4);
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .close-modal-btn {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 0.625rem;
            transition: all 0.2s;
        }

        .close-modal-btn:hover {
            background: #e2e8f0;
        }

        body.modal-open {
            overflow: hidden;
        }

        /* Pulse animation for lock icon */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .pulse-icon {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-[#f0f4ff] font-['Segoe_UI',system-ui,sans-serif] min-h-screen {{ $showCodeModal ? 'modal-open' : '' }}">

{{-- HEADER --}}
<div class="sticky top-0 z-50 bg-[#1a56db] text-white px-6 py-5 shadow-[0_2px_10px_rgba(26,86,219,0.3)] {{ $showCodeModal ? 'opacity-50' : '' }}">
    <div class="max-w-[1000px] mx-auto">
        <div class="flex justify-between items-start">
            <div>
                <h5 class="text-lg font-bold m-0">{{ $votingSession->title }}</h5>
                <small class="opacity-70 text-[0.82rem]">You may skip positions you don't wish to vote</small>
            </div>
            <a href="{{ route('student.dashboard') }}" class="text-white/70 text-sm no-underline hover:text-white">✕</a>
        </div>
        <div class="bg-white/20 rounded h-1 overflow-hidden mt-2">
            <div class="bg-[#93c5fd] h-full rounded transition-all duration-300" id="progressFill" style="width:0%"></div>
        </div>
        <div class="text-xs opacity-60 mt-1">
            <span id="progressText">0</span> of {{ $votingSession->positions->count() }} positions reviewed
        </div>
    </div>
</div>

<div class="max-w-[1000px] mx-auto py-6 px-4 {{ $showCodeModal ? 'opacity-50 pointer-events-none' : '' }}">

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 flex items-center gap-2">
        <i class="bi bi-exclamation-circle"></i>{{ $errors->first() }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 flex items-center gap-2">
        <i class="bi bi-exclamation-circle"></i>{{ session('error') }}
    </div>
    @endif

    @if($alreadyVoted && $votingSession->allow_vote_changes)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 mb-4 flex items-center gap-2">
        <i class="bi bi-info-circle"></i>You have already voted. Submitting will replace your previous vote.
    </div>
    @endif

    <form method="POST" action="{{ route('student.vote', $votingSession) }}" id="ballotForm">
        @csrf

        @foreach($votingSession->positions as $index => $position)
        <div class="bg-white rounded-[20px] border border-slate-200 mb-8 overflow-hidden transition-all duration-300 shadow-sm" id="posContainer{{ $position->id }}">

            {{-- Position Header --}}
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-5 py-4 border-b-2 border-slate-200 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="w-8 h-8 rounded-full bg-[#1a56db] text-white flex items-center justify-center text-sm font-bold">{{ $index + 1 }}</div>
                    <div class="text-lg font-bold text-slate-800">{{ $position->title }}</div>
                    @if($position->max_winners > 1)
                        <span class="bg-[#e0e7ff] text-[#1e40af] text-[0.7rem] px-3 py-1 rounded-full font-medium">
                            <i class="bi bi-people mr-1"></i>Select up to {{ $position->max_winners }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400">
                        <i class="bi bi-people mr-1"></i>{{ $position->candidates->count() }} candidate(s)
                    </span>
                    <div class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-500" id="posStatus{{ $position->id }}">Not selected</div>
                </div>
            </div>

            {{-- Candidates --}}
            <div class="candidates-scroll-container" id="scrollContainer{{ $position->id }}">
                @foreach($position->candidates as $candidate)
                @php
                    $motto = $candidate->student->manifesto ?? null;
                    $platform = $candidate->student->platform ?? null;
                    $photoUrl = $candidate->photo_url;
                @endphp

                <div class="candidate-card group relative rounded-2xl overflow-hidden border-2 border-slate-200 hover:shadow-xl"
                     id="card-{{ $position->id }}-{{ $candidate->id }}"
                     onclick="event.stopPropagation(); toggleCandidate({{ $position->id }}, {{ $candidate->id }}); return false;">

                    <input type="checkbox"
                           name="votes[{{ $position->id }}][]"
                           value="{{ $candidate->id }}"
                           data-position="{{ $position->id }}"
                           data-max="{{ $position->max_winners }}"
                           class="candidate-checkbox hidden"
                           id="cb-{{ $position->id }}-{{ $candidate->id }}">

                    {{-- Check Indicator --}}
                    <div class="check-indicator absolute top-3 right-3 w-6 h-6 rounded-full bg-white border-2 border-slate-300 flex items-center justify-center transition-all z-10 shadow-sm"></div>

                    {{-- Gradient Background + Photo --}}
                    <div class="relative h-52 bg-gradient-to-br from-[#1a56db] via-[#7c3aed] to-[#f59e0b] overflow-hidden">
                        <div class="absolute -bottom-6 -left-6 w-40 h-40 rounded-full bg-white/10"></div>
                        <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/10"></div>
                        <img src="{{ $photoUrl }}"
                             class="absolute bottom-0 left-1/2 -translate-x-1/2 h-44 w-auto object-contain drop-shadow-2xl transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $candidate->full_name }}"
                             loading="lazy"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($candidate->full_name, 0, 2)) }}&background=1a56db&color=fff&size=200'">
                    </div>

                    {{-- Name Bar --}}
                    <div class="relative">
                        <div class="bg-slate-800 px-4 py-2">
                            <div class="text-white font-extrabold text-lg uppercase tracking-wide leading-tight">{{ $candidate->last_name ?? '' }}</div>
                        </div>
                        <div class="bg-[#dc2626] px-4 py-1.5">
                            <div class="text-white font-bold text-sm uppercase">{{ $candidate->first_name ?? $candidate->full_name }}</div>
                        </div>
                    </div>

                    {{-- Info Section --}}
                    <div class="px-4 py-3 bg-white">
                        <div class="flex gap-2 mb-3 flex-wrap">
                            <span class="bg-slate-100 text-slate-600 text-[0.7rem] px-2 py-0.5 rounded-full">
                                <i class="bi bi-building mr-1"></i>{{ $candidate->section ?? 'N/A' }}
                            </span>
                            @if($candidate->year_level)
                            <span class="bg-slate-100 text-slate-600 text-[0.7rem] px-2 py-0.5 rounded-full">
                                <i class="bi bi-calendar mr-1"></i>Year {{ $candidate->year_level }}
                            </span>
                            @endif
                        </div>

                        @if($motto)
                        <div class="bg-[#eff6ff] rounded-xl px-3 py-2 mb-2 flex items-start gap-1.5">
                            <i class="bi bi-quote text-[#1a56db] opacity-50 text-base mt-0.5 flex-shrink-0"></i>
                            <span class="text-[#1e293b] text-xs italic font-semibold leading-snug">{{ Str::limit($motto, 90) }}</span>
                        </div>
                        @endif

                        @if($platform)
                        <div class="bg-gradient-to-r from-cyan-400 to-teal-400 rounded-xl p-3">
                            <div class="text-[0.65rem] font-bold uppercase tracking-wider text-white/80 mb-1.5 flex items-center gap-1">
                                <i class="bi bi-list-check"></i> PLATFORM
                            </div>
                            <ul class="mb-0 pl-3" style="list-style:disc;color:white">
                                @foreach(array_slice(array_filter(explode("\n", $platform)), 0, 4) as $point)
                                    <li class="text-white text-xs leading-relaxed">{{ Str::limit(trim($point), 55) }}</li>
                                @endforeach
                                @if(count(array_filter(explode("\n", $platform))) > 4)
                                    <li class="text-white text-[0.65rem] italic">+ more</li>
                                @endif
                            </ul>
                        </div>
                        @elseif(!$motto)
                        <div class="bg-slate-50 rounded-xl p-3 text-center">
                            <i class="bi bi-chat-dots text-slate-300 text-lg"></i>
                            <p class="text-slate-400 text-[0.7rem] italic mt-1 mb-0">No platform provided yet.</p>
                        </div>
                        @endif
                    </div>
                </div>

                @endforeach
            </div>

            @if($position->candidates->count() === 0)
            <div class="p-8 text-center text-slate-400">
                <i class="bi bi-people text-4xl block mb-2 opacity-50"></i>
                <p class="mb-0">No candidates have applied for this position yet.</p>
                <small>You may skip this position.</small>
            </div>
            @endif

            {{-- Skip Option --}}
            <div class="bg-amber-50 border-t border-amber-200 px-5 py-3 flex items-center justify-between cursor-pointer transition-colors hover:bg-amber-100 skip-option-area"
                 id="skipOption{{ $position->id }}" onclick="event.stopPropagation(); toggleSkipPosition({{ $position->id }})">
                <div class="flex items-center gap-2 text-sm">
                    <i class="bi bi-eye-slash text-amber-500"></i>
                    <span>Abstain from voting for this position</span>
                </div>
                <div class="skip-checkbox w-5 h-5 rounded border-2 border-amber-400 flex items-center justify-center transition-all" id="skipCheckbox{{ $position->id }}"></div>
            </div>
        </div>
        @endforeach

        <div class="flex gap-3 mt-6">
            <a href="{{ route('student.dashboard') }}" class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-medium hover:bg-slate-50 transition-colors no-underline text-center">Cancel</a>
            <button type="submit" class="flex-1 bg-[#1a56db] text-white border-none rounded-xl py-3 text-base font-bold transition-all hover:bg-[#1447c0] hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed shadow-md" id="submitBtn" disabled>
                Submit My Votes →
            </button>
        </div>
    </form>
</div>

{{-- Release Code Modal --}}
@if($showCodeModal)
<div id="releaseCodeModal" class="code-modal-overlay">
    <div class="code-modal">
        <div class="text-center">
            <div class="modal-icon pulse-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h3 class="fw-bold text-2xl text-slate-800 mb-2">Secure Access Required</h3>
            <p class="text-slate-500 text-sm mb-3">
                This election requires a verification code
            </p>

            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-3 mb-4">
                <div class="flex items-center justify-center gap-2 text-xs text-slate-600">
                    <i class="bi bi-calendar-check text-primary"></i>
                    <span>{{ $votingSession->title }}</span>
                </div>
                <div class="flex items-center justify-center gap-2 text-xs text-slate-500 mt-1">
                    <i class="bi bi-clock"></i>
                    <span>Ends: {{ $votingSession->end_date->format('M d, Y h:i A') }}</span>
                </div>
            </div>

            <form id="releaseCodeForm">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 text-left mb-2">
                        <i class="bi bi-key me-1"></i> Enter Release Code
                    </label>
                    <input type="text"
                           name="release_code"
                           id="releaseCodeInput"
                           class="code-input-custom w-full"
                           placeholder="••••••••"
                           autocomplete="off"
                           required>
                    <div id="codeError" class="text-danger text-sm mt-2" style="display: none;"></div>
                </div>

                <button type="submit" id="verifyBtn" class="btn-verify w-full text-white">
                    <i class="bi bi-check2-circle me-2"></i> Verify & Access Ballot
                </button>
            </form>

            <div class="mt-4 pt-3 border-t border-slate-100">
                <div class="flex items-center justify-center gap-3 text-xs text-slate-400">
                    <span><i class="bi bi-shield-check"></i> Secure</span>
                    <span><i class="bi bi-clock-history"></i> One-time verification</span>
                </div>
                <a href="{{ route('student.dashboard') }}" class="text-slate-400 hover:text-slate-600 text-xs text-center block mt-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .text-primary { color: #1a56db; }
    .text-danger { color: #ef4444; }
    .bg-primary { background: #1a56db; }
    .btn-verify:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
</style>

<script>
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';

    // Handle form submission with AJAX
    const releaseCodeForm = document.getElementById('releaseCodeForm');
    const verifyBtn = document.getElementById('verifyBtn');
    const codeInput = document.getElementById('releaseCodeInput');
    const errorDiv = document.getElementById('codeError');

    if (releaseCodeForm) {
        releaseCodeForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const code = codeInput.value.trim();

            if (!code) {
                errorDiv.textContent = 'Please enter a release code.';
                errorDiv.style.display = 'block';
                codeInput.classList.add('is-invalid');
                return;
            }

            // Disable button and show loading
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...';

            try {
                const response = await fetch('{{ route("student.ballot.validate", $votingSession) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        release_code: code
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Success - reload the page to show ballot
                    window.location.reload();
                } else {
                    // Show error
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (data.message || 'Invalid or expired release code. Please check and try again.');
                    errorDiv.style.display = 'block';
                    codeInput.classList.add('is-invalid');
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '<i class="bi bi-check2-circle me-2"></i> Verify & Access Ballot';

                    // Shake animation for error
                    const modal = document.querySelector('.code-modal');
                    modal.style.animation = 'none';
                    setTimeout(() => {
                        modal.style.animation = 'modalSlideIn 0.4s cubic-bezier(0.34, 1.2, 0.64, 1)';
                    }, 10);
                }
            } catch (error) {
                errorDiv.innerHTML = '<i class="bi bi-wifi-off me-1"></i> Network error. Please try again.';
                errorDiv.style.display = 'block';
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="bi bi-check2-circle me-2"></i> Verify & Access Ballot';
            }
        });

        // Remove error styling when user types
        codeInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            errorDiv.style.display = 'none';
        });
    }
</script>
@endif

<script>
    // Only initialize ballot JS if modal is not shown
    @if(!$showCodeModal)
    document.addEventListener('DOMContentLoaded', function() {
        const totalPositions = {{ $votingSession->positions->count() }};
        let selections = {};
        let skippedPositions = {};

        function updateProgress() {
            const votedCount = Object.keys(selections).filter(posId => selections[posId] && selections[posId].length > 0).length;
            const skippedCount = Object.keys(skippedPositions).filter(posId => skippedPositions[posId] === true).length;
            const reviewedCount = votedCount + skippedCount;

            document.getElementById('progressText').textContent = reviewedCount;
            const progressPercent = totalPositions > 0 ? (reviewedCount / totalPositions) * 100 : 0;
            document.getElementById('progressFill').style.width = progressPercent + '%';

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) submitBtn.disabled = reviewedCount < totalPositions;

            @foreach($votingSession->positions as $position)
                updatePositionStatus({{ $position->id }});
            @endforeach
        }

        function updatePositionStatus(posId) {
            const statusEl = document.getElementById(`posStatus${posId}`);
            const container = document.getElementById(`posContainer${posId}`);

            if (!statusEl) return;

            if (selections[posId] && selections[posId].length > 0) {
                statusEl.textContent = `✓ ${selections[posId].length} selected`;
                statusEl.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-green-100 text-green-700';
                if (container) container.classList.remove('opacity-60');
            } else if (skippedPositions[posId]) {
                statusEl.textContent = '⨯ Skipped';
                statusEl.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-amber-100 text-amber-700';
                if (container) container.classList.add('opacity-60');
            } else {
                statusEl.textContent = 'Not selected';
                statusEl.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-500';
                if (container) container.classList.remove('opacity-60');
            }
        }

        window.toggleCandidate = function(posId, candidateId) {
            const checkbox = document.getElementById(`cb-${posId}-${candidateId}`);
            const card = document.getElementById(`card-${posId}-${candidateId}`);

            if (!checkbox || !card) return;

            const max = parseInt(checkbox.dataset.max);
            const checked = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]:checked`);

            if (!checkbox.checked && checked.length >= max) {
                alert(`You can only select up to ${max} candidate(s) for this position.`);
                return;
            }

            checkbox.checked = !checkbox.checked;

            if (checkbox.checked) {
                card.classList.add('!border-[#1a56db]', '!shadow-[0_0_0_3px_rgba(26,86,219,0.2)]');
                const indicator = card.querySelector('.check-indicator');
                if (indicator) {
                    indicator.classList.add('!bg-[#1a56db]', '!border-[#1a56db]');
                    indicator.innerHTML = '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
                }
            } else {
                card.classList.remove('!border-[#1a56db]', '!shadow-[0_0_0_3px_rgba(26,86,219,0.2)]');
                const indicator = card.querySelector('.check-indicator');
                if (indicator) {
                    indicator.classList.remove('!bg-[#1a56db]', '!border-[#1a56db]');
                    indicator.innerHTML = '';
                }
            }

            const rechecked = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]:checked`);
            if (rechecked.length > 0) {
                selections[posId] = Array.from(rechecked).map(cb => cb.value);
                if (skippedPositions[posId]) {
                    skippedPositions[posId] = false;
                    const skipOption = document.getElementById(`skipOption${posId}`);
                    const skipCheckbox = document.getElementById(`skipCheckbox${posId}`);
                    if (skipOption) skipOption.classList.remove('!bg-amber-100');
                    if (skipCheckbox) {
                        skipCheckbox.classList.remove('!bg-amber-400');
                        skipCheckbox.innerHTML = '';
                    }
                }
            } else {
                delete selections[posId];
            }

            updateProgress();
        };

        window.toggleSkipPosition = function(posId) {
            const skipOption = document.getElementById(`skipOption${posId}`);
            const skipCheckbox = document.getElementById(`skipCheckbox${posId}`);

            if (selections[posId] && selections[posId].length > 0) {
                if (!confirm('You have selected candidates for this position. Skipping will clear your selections. Continue?')) return;

                document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]`).forEach(cb => {
                    cb.checked = false;
                    const card = document.getElementById(`card-${posId}-${cb.value}`);
                    if (card) {
                        card.classList.remove('!border-[#1a56db]', '!shadow-[0_0_0_3px_rgba(26,86,219,0.2)]');
                        const indicator = card.querySelector('.check-indicator');
                        if (indicator) {
                            indicator.classList.remove('!bg-[#1a56db]', '!border-[#1a56db]');
                            indicator.innerHTML = '';
                        }
                    }
                });
                delete selections[posId];
            }

            if (skippedPositions[posId]) {
                skippedPositions[posId] = false;
                if (skipOption) skipOption.classList.remove('!bg-amber-100');
                if (skipCheckbox) {
                    skipCheckbox.classList.remove('!bg-amber-400');
                    skipCheckbox.innerHTML = '';
                }
            } else {
                skippedPositions[posId] = true;
                if (skipOption) skipOption.classList.add('!bg-amber-100');
                if (skipCheckbox) {
                    skipCheckbox.classList.add('!bg-amber-400');
                    skipCheckbox.innerHTML = '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
                }
            }

            updateProgress();
        };

        // Initialize existing selections
        document.querySelectorAll('.candidate-checkbox:checked').forEach(checkbox => {
            const posId = checkbox.dataset.position;
            const candidateId = checkbox.value;
            if (!selections[posId]) selections[posId] = [];
            if (!selections[posId].includes(candidateId)) selections[posId].push(candidateId);
            const card = document.getElementById(`card-${posId}-${candidateId}`);
            if (card) {
                card.classList.add('!border-[#1a56db]', '!shadow-[0_0_0_3px_rgba(26,86,219,0.2)]');
                const indicator = card.querySelector('.check-indicator');
                if (indicator) {
                    indicator.classList.add('!bg-[#1a56db]', '!border-[#1a56db]');
                    indicator.innerHTML = '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
                }
            }
        });

        updateProgress();

        // Fade mask only on mobile — remove when scrolled to end
        document.querySelectorAll('.candidates-scroll-container').forEach(container => {
            const check = () => {
                const atEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;
                container.classList.toggle('at-end', atEnd);
            };
            container.addEventListener('scroll', check);
            check();
        });

        // Form submission
        document.getElementById('ballotForm')?.addEventListener('submit', function(e) {
            const reviewedCount = parseInt(document.getElementById('progressText').textContent);

            if (reviewedCount < totalPositions) {
                e.preventDefault();
                alert(`Please either vote or skip all positions before submitting.\n\nYou have reviewed ${reviewedCount} out of ${totalPositions} positions.`);
                return false;
            }

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span> Submitting...';
            }
        });
    });
    @endif
</script>
</body>
</html>
