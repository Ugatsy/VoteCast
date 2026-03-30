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
        .candidate-card { animation: fadeIn 0.2s ease-out; }
        .candidate-card { cursor: pointer; }
    </style>
</head>
<body class="bg-[#f0f4ff] font-['Segoe_UI',system-ui,sans-serif] min-h-screen">

{{-- HEADER --}}
<div class="sticky top-0 z-50 bg-[#1a56db] text-white px-6 py-5 shadow-[0_2px_10px_rgba(26,86,219,0.3)]">
    <div class="max-w-[1000px] mx-auto">
        <div class="flex justify-between items-start">
            <div>
                <h5 class="text-lg font-bold m-0">{{ $votingSession->title }}</h5>
                <small class="opacity-70 text-[0.82rem]">{{ $votingSession->positions->count() }} position(s) · You may skip positions you don't wish to vote for</small>
            </div>
            <a href="{{ route('student.dashboard') }}" class="text-white/70 text-sm no-underline hover:text-white">✕ Cancel</a>
        </div>
        <div class="bg-white/20 rounded h-1 overflow-hidden mt-2">
            <div class="bg-[#93c5fd] h-full rounded transition-all duration-300" id="progressFill" style="width:0%"></div>
        </div>
        <div class="text-xs opacity-60 mt-1">
            <span id="progressText">0</span> of {{ $votingSession->positions->count() }} positions reviewed
        </div>
    </div>
</div>

<div class="max-w-[1000px] mx-auto py-6 px-4">

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

    <div class="bg-[#e0e7ff] rounded-lg px-4 py-3 mb-4 text-sm text-[#1e40af] flex items-center gap-2">
        <i class="bi bi-info-circle-fill"></i>
        <span>Click on any candidate card to vote for them. For positions with multiple winners, you can select up to the specified limit.</span>
    </div>

    <form method="POST" action="{{ route('student.vote', $votingSession) }}" id="ballotForm">
        @csrf

        @if($votingSession->requires_release_code)
        <div class="bg-white rounded-2xl border-2 border-amber-400 p-5 mb-6">
            <label class="block text-sm font-bold text-amber-800 mb-2">
                <i class="bi bi-key-fill mr-1"></i>Release Code Required
            </label>
            <input type="text" name="release_code"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('release_code') border-red-500 @enderror"
                   placeholder="Enter your release code" required>
            @error('release_code')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        @endif

        @foreach($votingSession->positions as $index => $position)
        <div class="bg-white rounded-[20px] border border-slate-200 mb-8 overflow-hidden transition-all duration-300" id="posContainer{{ $position->id }}">
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
                <div class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-500" id="posStatus{{ $position->id }}">Not selected</div>
            </div>

            @if($position->max_winners > 1)
            <div class="bg-amber-50 border-b border-amber-200 px-5 py-2 text-xs text-amber-800 flex items-center gap-2">
                <i class="bi bi-info-circle"></i>
                <span>You can vote for multiple candidates in this position</span>
                <span class="ml-auto font-semibold" id="selectedCounter{{ $position->id }}">(0 selected)</span>
            </div>
            @endif

            {{-- Candidates Grid --}}
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($position->candidates as $candidate)
                @php
                    $motto = $candidate->student->manifesto ?? null;
                    $platform = $candidate->student->platform ?? null;
                    $photoUrl = $candidate->photo_url;
                @endphp

                {{-- ===== PROFILE CONTAINER CARD ===== --}}
                <div class="candidate-card group relative rounded-2xl overflow-hidden cursor-pointer transition-all duration-200 border-2 border-slate-200 hover:shadow-xl hover:-translate-y-1 hover:border-slate-300"
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
                    <div class="check-indicator absolute top-3 right-3 w-6 h-6 rounded-full bg-white border-2 border-slate-300 flex items-center justify-center transition-all z-10"></div>

                    {{-- Gradient Background + Photo --}}
                    <div class="relative h-52 bg-gradient-to-br from-[#1a56db] via-[#7c3aed] to-[#f59e0b] overflow-hidden">
                        <div class="absolute -bottom-6 -left-6 w-40 h-40 rounded-full bg-white/10"></div>
                        <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/10"></div>

                        <img src="{{ $photoUrl }}"
                             class="absolute bottom-0 left-1/2 -translate-x-1/2 h-44 w-auto object-contain drop-shadow-2xl transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $candidate->full_name }}"
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
                        <div class="flex gap-2 mb-3">
                            <span class="bg-slate-100 text-slate-600 text-[0.7rem] px-2 py-0.5 rounded-full">
                                <i class="bi bi-building mr-1"></i>{{ $candidate->section }}
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
                            <span class="text-[#1e293b] text-xs italic font-semibold leading-snug">{{ $motto }}</span>
                        </div>
                        @endif

                        @if($platform)
                        <div class="bg-gradient-to-r from-cyan-400 to-teal-400 rounded-xl p-3">
                            <div class="text-[0.65rem] font-bold uppercase tracking-wider text-white/80 mb-1.5 flex items-center gap-1">
                                <i class="bi bi-list-check"></i> PLATFORM
                            </div>
                            <ul class="mb-0 pl-3" style="list-style:disc;color:white">
                                @foreach(array_slice(array_filter(explode("\n", $platform)), 0, 4) as $point)
                                    <li class="text-white text-xs leading-relaxed">{{ trim($point) }}</li>
                                @endforeach
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
            <div class="bg-amber-50 border-t border-amber-200 px-5 py-3 flex items-center justify-between cursor-pointer transition-colors hover:bg-amber-100"
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
            <a href="{{ route('student.dashboard') }}" class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-medium hover:bg-slate-50 transition-colors no-underline">Cancel</a>
            <button type="submit" class="flex-1 bg-[#1a56db] text-white border-none rounded-xl py-3 text-base font-bold transition-all hover:bg-[#1447c0] hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed" id="submitBtn" disabled>
                Submit My Votes →
            </button>
        </div>

        <p class="text-center text-slate-400 text-sm mt-4">
            <i class="bi bi-lock mr-1"></i>Your vote is anonymous and securely recorded.
            <br>You may abstain from any position by checking the skip option.
        </p>
    </form>
</div>

<script>
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
            if (submitBtn) {
                submitBtn.disabled = reviewedCount < totalPositions;
            }

            // Update status for all positions
            @foreach($votingSession->positions as $position)
                updatePositionStatus({{ $position->id }});
            @endforeach
        }

        function updatePositionStatus(posId) {
            const statusEl = document.getElementById(`posStatus${posId}`);
            const container = document.getElementById(`posContainer${posId}`);
            const counterEl = document.getElementById(`selectedCounter${posId}`);

            if (!statusEl) return;

            if (selections[posId] && selections[posId].length > 0) {
                statusEl.textContent = `✓ ${selections[posId].length} selected`;
                statusEl.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-green-100 text-green-700';
                if (container) { 
                    container.classList.remove('opacity-60'); 
                }
                if (counterEl) counterEl.textContent = `(${selections[posId].length} selected)`;
            } else if (skippedPositions[posId]) {
                statusEl.textContent = '⨯ Skipped';
                statusEl.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-amber-100 text-amber-700';
                if (container) container.classList.add('opacity-60');
                if (counterEl) counterEl.textContent = '(0 selected)';
            } else {
                statusEl.textContent = 'Not selected';
                statusEl.className = 'text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-500';
                if (container) container.classList.remove('opacity-60');
                if (counterEl) counterEl.textContent = '(0 selected)';
            }
        }

        window.toggleCandidate = function(posId, candidateId) {
            const checkbox = document.getElementById(`cb-${posId}-${candidateId}`);
            const card = document.getElementById(`card-${posId}-${candidateId}`);
            
            if (!checkbox || !card) {
                console.error('Checkbox or card not found', posId, candidateId);
                return;
            }
            
            const max = parseInt(checkbox.dataset.max);
            const checked = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]:checked`);

            // If trying to check and already at max, prevent
            if (!checkbox.checked && checked.length >= max) {
                alert(`You can only select up to ${max} candidate(s) for this position.`);
                return;
            }

            // Toggle checkbox
            checkbox.checked = !checkbox.checked;

            // Update card styling
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

            // Update selections object
            const rechecked = document.querySelectorAll(`.candidate-checkbox[data-position="${posId}"]:checked`);
            if (rechecked.length > 0) {
                selections[posId] = Array.from(rechecked).map(cb => cb.value);
                // If we selected a candidate, remove from skipped
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

                // Clear all checkboxes for this position
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
                // Unskip
                skippedPositions[posId] = false;
                if (skipOption) skipOption.classList.remove('!bg-amber-100');
                if (skipCheckbox) { 
                    skipCheckbox.classList.remove('!bg-amber-400'); 
                    skipCheckbox.innerHTML = ''; 
                }
            } else {
                // Skip
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

        // Form submission handler
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
</script>
</body>
</html>
