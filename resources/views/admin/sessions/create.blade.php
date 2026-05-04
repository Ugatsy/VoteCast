@extends('layouts.admin')
@section('title', 'Create Election')

@section('content')

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-plus-circle me-2 text-primary"></i>New Election</strong>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.sessions.store') }}" id="electionForm">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold small">Election Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" placeholder="e.g. BSIT Student Council Election 2025" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Description</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Optional details about this election">{{ old('description') }}</textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Start Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="start_date"
                           class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date') }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">End Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="end_date"
                           class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date') }}" required>
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Who Can Vote? <span class="text-danger">*</span></label>
                <select name="category" class="form-select" id="categorySelect" required>
                    <option value="">— Select eligibility type —</option>
                    <option value="course"     @selected(old('category') === 'course')>By Course</option>
                    <option value="section"    @selected(old('category') === 'section')>By Section</option>
                    <option value="department" @selected(old('category') === 'department')>By Department</option>
                    <option value="manual"     @selected(old('category') === 'manual')>Manual (specific students)</option>
                </select>
            </div>

            <div class="mb-3 d-none" id="courseField">
                <label class="form-label fw-semibold small">Target Course</label>
                <select name="target_course" class="form-select">
                    <option value="">— Select course —</option>
                    @foreach($courses as $course)
                        <option value="{{ $course }}" @selected(old('target_course') === $course)>{{ $course }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3 d-none" id="sectionField">
                <label class="form-label fw-semibold small">Target Section</label>
                <select name="target_section" class="form-select">
                    <option value="">— Select section —</option>
                    @foreach($sections as $section)
                        <option value="{{ $section }}" @selected(old('target_section') === $section)>{{ $section }}</option>
                    @endforeach
                </select>
                <div class="form-text text-muted">Only students in this section will be able to vote.</div>
            </div>

            <div class="mb-3 d-none" id="deptField">
                <label class="form-label fw-semibold small">Target Department</label>
                <input type="text" name="target_department" class="form-control"
                       value="{{ old('target_department') }}" placeholder="e.g. CICT">
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_vote_changes"
                               id="allowChanges" value="1" @checked(old('allow_vote_changes'))>
                        <label class="form-check-label small" for="allowChanges">Allow voters to change their vote</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_release_code"
                               id="releaseCodeToggle" value="1" @checked(old('requires_release_code'))>
                        <label class="form-check-label small" for="releaseCodeToggle">Require release code to vote</label>
                    </div>
                </div>
            </div>

            {{-- Release Code Section with Live QR Code --}}
            <div id="releaseCodeSection" class="mb-4 p-3 bg-light rounded" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-semibold small">
                        <i class="bi bi-qr-code me-1 text-primary"></i>Release Code Configuration
                    </label>
                    <button type="button" id="generateRandomCode" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-shuffle me-1"></i>Generate Random Code
                    </button>
                </div>

                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Codes will automatically expire when the election ends on <strong id="expiryDatePreview">{{ old('end_date') ? date('M d, Y H:i', strtotime(old('end_date'))) : 'the end date' }}</strong>.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Release Code(s) with QR</label>
                    <div id="codeInputsContainer">
                        <div class="code-card mb-3 p-3 border rounded bg-white" id="codeCard0">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <label class="small text-muted mb-1">Release Code</label>
                                    <div class="input-group">
                                        <input type="text" name="release_codes[]" class="form-control release-code-input"
                                               placeholder="Enter release code" value="{{ old('release_codes.0') }}"
                                               oninput="updateQRCode(0, this.value)">
                                        <button type="button" class="btn btn-outline-danger remove-code-btn" style="display: none;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="text-center">
                                        <label class="small text-muted mb-1">QR Code (Students can scan)</label>
                                        <div id="qrCode0" class="qr-display p-2 bg-white rounded d-flex justify-content-center align-items-center" style="min-height: 120px;">
                                            <div class="text-muted small">Enter a code to generate QR</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="addMoreCodes" class="btn btn-sm btn-link text-primary p-0 mt-2">
                        <i class="bi bi-plus-circle me-1"></i>Add another code
                    </button>
                    <div class="form-text mt-2">Each code has its own QR code. Students can type OR scan.</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary flex-grow-1">
                    Continue: Add Positions & Candidates →
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs2@0.0.2/qrcode.min.js"></script>
<script>
    let qrInstances = {};
    let codeCounter = 1;

    // Toggle release code section
    const releaseCodeToggle = document.getElementById('releaseCodeToggle');
    const releaseCodeSection = document.getElementById('releaseCodeSection');
    const endDateInput = document.querySelector('input[name="end_date"]');
    const expiryDatePreview = document.getElementById('expiryDatePreview');

    function toggleReleaseCodeSection() {
        if (releaseCodeToggle.checked) {
            releaseCodeSection.style.display = 'block';
            updateExpiryPreview();
            // Initialize QR for existing inputs
            document.querySelectorAll('.release-code-input').forEach((input, idx) => {
                if (input.value) {
                    updateQRCode(idx, input.value);
                }
            });
        } else {
            releaseCodeSection.style.display = 'none';
        }
    }

    function updateExpiryPreview() {
        if (endDateInput && endDateInput.value) {
            const endDate = new Date(endDateInput.value);
            const formattedDate = endDate.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            expiryDatePreview.textContent = formattedDate;
        } else {
            expiryDatePreview.textContent = 'the election end date';
        }
    }

    function generateQRCode(elementId, code) {
        if (!code || code.trim() === '') {
            document.getElementById(elementId).innerHTML = '<div class="text-muted small">Enter a code to generate QR</div>';
            return;
        }

        // Clear previous QR
        document.getElementById(elementId).innerHTML = '';

        // Generate new QR code
        try {
            new QRCode(document.getElementById(elementId), {
                text: code,
                width: 100,
                height: 100,
                colorDark: "#1a56db",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        } catch(e) {
            document.getElementById(elementId).innerHTML = '<div class="text-muted small">QR generation error</div>';
        }
    }

    function updateQRCode(index, code) {
        const elementId = `qrCode${index}`;
        const container = document.getElementById(elementId);
        if (container) {
            if (qrInstances[index]) {
                container.innerHTML = '';
            }
            generateQRCode(elementId, code);
        }
    }

    function addNewCodeCard() {
        const container = document.getElementById('codeInputsContainer');
        const newIndex = codeCounter;

        const newCard = document.createElement('div');
        newCard.className = 'code-card mb-3 p-3 border rounded bg-white';
        newCard.id = `codeCard${newIndex}`;
        newCard.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-5">
                    <label class="small text-muted mb-1">Release Code</label>
                    <div class="input-group">
                        <input type="text" name="release_codes[]" class="form-control release-code-input"
                               placeholder="Enter release code" oninput="updateQRCode(${newIndex}, this.value)">
                        <button type="button" class="btn btn-outline-danger remove-code-btn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="text-center">
                        <label class="small text-muted mb-1">QR Code (Students can scan)</label>
                        <div id="qrCode${newIndex}" class="qr-display p-2 bg-white rounded d-flex justify-content-center align-items-center" style="min-height: 120px;">
                            <div class="text-muted small">Enter a code to generate QR</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(newCard);
        qrInstances[newIndex] = null;
        codeCounter++;

        // Show all remove buttons if more than 1
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        const cards = document.querySelectorAll('.code-card');
        const removeBtns = document.querySelectorAll('.remove-code-btn');

        if (cards.length === 1) {
            removeBtns.forEach(btn => btn.style.display = 'none');
        } else {
            removeBtns.forEach(btn => btn.style.display = 'inline-flex');
        }
    }

    function generateRandomCode(length = 8) {
        const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return result;
    }

    // Event Listeners
    releaseCodeToggle.addEventListener('change', toggleReleaseCodeSection);
    if (endDateInput) {
        endDateInput.addEventListener('change', updateExpiryPreview);
    }
    toggleReleaseCodeSection();

    document.getElementById('generateRandomCode').addEventListener('click', function() {
        const randomCode = generateRandomCode(8);
        const firstInput = document.querySelector('.release-code-input');
        if (firstInput) {
            firstInput.value = randomCode;
            updateQRCode(0, randomCode);
        }
    });

    document.getElementById('addMoreCodes').addEventListener('click', addNewCodeCard);

    // Initialize remove buttons and QR for existing
    document.addEventListener('DOMContentLoaded', function() {
        updateRemoveButtons();

        // Initialize QR for any existing codes
        document.querySelectorAll('.release-code-input').forEach((input, idx) => {
            if (input.value) {
                updateQRCode(idx, input.value);
            }
            input.addEventListener('input', function() {
                updateQRCode(idx, this.value);
            });
        });
    });

    // Remove code card (delegation)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-code-btn')) {
            const btn = e.target.closest('.remove-code-btn');
            const card = btn.closest('.code-card');
            if (card && document.querySelectorAll('.code-card').length > 1) {
                card.remove();
                updateRemoveButtons();
            }
        }
    });

    // Category toggle
    const sel = document.getElementById('categorySelect');
    const courseF = document.getElementById('courseField');
    const sectionF = document.getElementById('sectionField');
    const deptF = document.getElementById('deptField');

    function toggleFields() {
        courseF.classList.add('d-none');
        sectionF.classList.add('d-none');
        deptF.classList.add('d-none');

        if (sel.value === 'course') courseF.classList.remove('d-none');
        if (sel.value === 'section') sectionF.classList.remove('d-none');
        if (sel.value === 'department') deptF.classList.remove('d-none');
    }

    sel.addEventListener('change', toggleFields);
    toggleFields();

    // Form validation - ensure codes are not empty
    document.getElementById('electionForm').addEventListener('submit', function(e) {
        if (releaseCodeToggle.checked) {
            const codeInputs = document.querySelectorAll('.release-code-input');
            let hasEmpty = false;
            codeInputs.forEach(input => {
                if (!input.value.trim()) {
                    hasEmpty = true;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            if (hasEmpty) {
                e.preventDefault();
                alert('Please enter release codes for all fields or remove empty ones.');
            }
        }
    });
</script>
@endpush
