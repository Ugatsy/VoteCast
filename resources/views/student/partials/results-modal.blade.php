{{-- Results Modal with Auto-Refresh --}}
<div class="modal fade" id="resultsModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content results-modal">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white;">
                <h5 class="modal-title" style="font-size:1rem">
                    <i class="bi bi-bar-chart-fill me-2"></i>
                    <span id="resultsModalTitle">Election Results</span>
                </h5>

                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="autoRefreshToggle" checked>
                        <label class="form-check-label small" for="autoRefreshToggle" style="font-size:0.7rem">Auto-refresh</label>
                    </div>

                    <button class="btn btn-sm btn-light" type="button" onclick="refreshResults()" style="font-size:0.7rem">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body" id="resultsModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading results...</p>
                </div>
            </div>

            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <span class="last-update text-muted small" id="modalLastUpdate"></span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSessionId = null;
    let refreshInterval = null;
    let isModalOpen = false;

    async function showLiveResults(sessionId) {
        currentSessionId = sessionId;

        const modalEl = document.getElementById('resultsModal');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const modalTitle = document.getElementById('resultsModalTitle');
        const modalContent = document.getElementById('resultsModalContent');
        const lastUpdateSpan = document.getElementById('modalLastUpdate');

        if (lastUpdateSpan) lastUpdateSpan.innerHTML = '';

        // Reset content
        modalTitle.innerHTML = '<i class="bi bi-bar-chart-fill me-2"></i>Loading Results...';
        modalContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Fetching results...</p>
            </div>
        `;

        modal.show();
        isModalOpen = true;

        await loadResults(sessionId);

        // Start auto-refresh if enabled
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        if (autoRefreshToggle && autoRefreshToggle.checked) {
            startAutoRefresh(sessionId);
        }
    }

    async function loadResults(sessionId) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch(`/results/${sessionId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!data.success) {
                document.getElementById('resultsModalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        ${data.error || 'Unable to load results.'}
                    </div>
                `;
                return;
            }

            updateResultsUI(data);
        } catch (error) {
            console.error('Error loading results:', error);
            document.getElementById('resultsModalContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-wifi-off me-2"></i>
                    Network error. Please try again.
                </div>
            `;
        }
    }

    function updateResultsUI(data) {
        const modalTitle = document.getElementById('resultsModalTitle');
        const modalContent = document.getElementById('resultsModalContent');
        const lastUpdateSpan = document.getElementById('modalLastUpdate');

        modalTitle.innerHTML = `
            <i class="bi bi-bar-chart-fill me-2"></i>
            ${data.session_title} - ${data.status === 'active' ? 'Live Results' : 'Final Results'}
        `;

        if (lastUpdateSpan) {
            lastUpdateSpan.innerHTML = `<i class="bi bi-clock me-1"></i>Updated: ${data.last_update}`;
        }

        let resultsHtml = `
            <div class="results-container" style="max-height: 60vh; overflow-y: auto;">
                <div class="row g-2 mb-4">
                    <div class="col-4">
                        <div class="bg-light rounded p-2 text-center">
                            <div class="small text-muted">Total Voters</div>
                            <div class="fw-bold fs-5">${data.total_voters}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-light rounded p-2 text-center">
                            <div class="small text-muted">Votes Cast</div>
                            <div class="fw-bold fs-5">${data.total_voted}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-light rounded p-2 text-center">
                            <div class="small text-muted">Turnout</div>
                            <div class="fw-bold fs-5">${data.turnout}%</div>
                        </div>
                    </div>
                </div>
        `;

        if (data.has_voted && data.status === 'active') {
            resultsHtml += `
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    You have already voted in this election.
                </div>
            `;
        }

        data.results.forEach(position => {
            resultsHtml += `
                <div class="card mb-3">
                    <div class="card-header bg-white fw-bold" style="border-bottom: 2px solid #e5e7eb;">
                        ${position.title}
                        ${position.max_winners > 1 ? `<span class="badge bg-primary ms-2">${position.max_winners} winners</span>` : ''}
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
            `;

            position.candidates.forEach(candidate => {
                const isWinner = candidate.is_winner;
                resultsHtml += `
                    <div class="list-group-item ${isWinner ? 'bg-success bg-opacity-10' : ''}">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <img src="${candidate.photo}" alt="${candidate.name}"
                                     style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background:#f1f5f9;">
                                <div>
                                    <div class="fw-semibold">
                                        ${candidate.name}
                                        ${isWinner ? '<span class="badge bg-success ms-2"><i class="bi bi-trophy"></i> Winner</span>' : ''}
                                    </div>
                                    <div class="small text-muted">${candidate.section}</div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">${candidate.vote_count} votes</div>
                                <div class="small text-muted">${candidate.percentage}%</div>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar ${isWinner ? 'bg-success' : 'bg-primary'}"
                                 style="width: ${candidate.percentage}%"></div>
                        </div>
                    </div>
                `;
            });

            resultsHtml += `
                        </div>
                    </div>
                </div>
            `;
        });

        resultsHtml += `</div>`;
        modalContent.innerHTML = resultsHtml;
    }

    function startAutoRefresh(sessionId) {
        if (refreshInterval) clearInterval(refreshInterval);

        refreshInterval = setInterval(async () => {
            const toggle = document.getElementById('autoRefreshToggle');
            if (isModalOpen && toggle && toggle.checked) {
                await loadResults(sessionId);
            }
        }, 10000);
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    function refreshResults() {
        if (currentSessionId) {
            loadResults(currentSessionId);
        }
    }

    function showFinalResults(sessionId) {
        return showLiveResults(sessionId);
    }

    // Modal events
    document.getElementById('resultsModal')?.addEventListener('hidden.bs.modal', function() {
        isModalOpen = false;
        stopAutoRefresh();
    });

    document.getElementById('resultsModal')?.addEventListener('shown.bs.modal', function() {
        isModalOpen = true;
        const toggle = document.getElementById('autoRefreshToggle');
        if (toggle && toggle.checked) {
            startAutoRefresh(currentSessionId);
        }
    });

    // Expose functions for inline onclick handlers
    window.showLiveResults = showLiveResults;
    window.showFinalResults = showFinalResults;
    window.refreshResults = refreshResults;
</script>
