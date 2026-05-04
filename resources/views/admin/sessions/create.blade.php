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

            {{-- Basic Info --}}
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

            {{-- Dates --}}
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

            {{-- Eligibility Category --}}
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

            {{-- Course target --}}
            <div class="mb-3 d-none" id="courseField">
                <label class="form-label fw-semibold small">Target Course</label>
                <select name="target_course" class="form-select">
                    <option value="">— Select course —</option>
                    @foreach($courses as $course)
                        <option value="{{ $course }}" @selected(old('target_course') === $course)>{{ $course }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Section target --}}
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

            {{-- Department target --}}
            <div class="mb-3 d-none" id="deptField">
                <label class="form-label fw-semibold small">Target Department</label>
                <input type="text" name="target_department" class="form-control"
                       value="{{ old('target_department') }}" placeholder="e.g. CICT">
            </div>

            {{-- Options --}}
            <div class="row g-3 mb-4">
                {{-- <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_vote_changes"
                               id="allowChanges" value="1" @checked(old('allow_vote_changes'))>
                        <label class="form-check-label small" for="allowChanges">Allow voters to change their vote</label>
                    </div>
                </div> --}}
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_release_code"
                               id="releaseCodeToggle" value="1" @checked(old('requires_release_code'))>
                        <label class="form-check-label small" for="releaseCodeToggle">Require release code to vote</label>
                    </div>
                </div>
            </div>

            {{-- Release Code Section (shows only when toggle is ON) --}}
            <div id="releaseCodeSection" class="mb-4 p-3 bg-light rounded" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-semibold small">
                        <i class="bi bi-qr-code me-1 text-primary"></i>Release Code Configuration
                    </label>
                    <button type="button" id="generateRandomCode" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-shuffle me-1"></i>Generate Random
                    </button>
                </div>

                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Codes will automatically expire when the election ends on <strong id="expiryDatePreview">{{ old('end_date') ? date('M d, Y H:i', strtotime(old('end_date'))) : 'the end date' }}</strong>.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Release Code(s)</label>
                    <div id="codeInputsContainer">
                        <div class="input-group mb-2 code-input-group">
                            <input type="text" name="release_codes[]" class="form-control release-code-input"
                                   placeholder="Enter release code (e.g., STUDENT2025)" value="{{ old('release_codes.0') }}">
                            <button type="button" class="btn btn-outline-danger remove-code-btn" style="display: none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" id="addMoreCodes" class="btn btn-sm btn-link text-primary p-0 mt-1">
                        <i class="bi bi-plus-circle me-1"></i>Add another code
                    </button>
                    <div class="form-text">Students will need to enter one of these codes to access the voting ballot.</div>
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
<script>
    // Toggle release code section
    const releaseCodeToggle = document.getElementById('releaseCodeToggle');
    const releaseCodeSection = document.getElementById('releaseCodeSection');
    const endDateInput = document.querySelector('input[name="end_date"]');
    const expiryDatePreview = document.getElementById('expiryDatePreview');

    function toggleReleaseCodeSection() {
        if (releaseCodeToggle.checked) {
            releaseCodeSection.style.display = 'block';
            updateExpiryPreview();
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

    releaseCodeToggle.addEventListener('change', toggleReleaseCodeSection);
    if (endDateInput) {
        endDateInput.addEventListener('change', updateExpiryPreview);
    }
    toggleReleaseCodeSection();

    // Generate random code
    function generateRandomCode(length = 8) {
        const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return result;
    }

    document.getElementById('generateRandomCode').addEventListener('click', function() {
        const firstInput = document.querySelector('.release-code-input');
        if (firstInput) {
            firstInput.value = generateRandomCode(8);
        }
    });

    // Add more code inputs
    let codeCount = 1;
    document.getElementById('addMoreCodes').addEventListener('click', function() {
        codeCount++;
        const container = document.getElementById('codeInputsContainer');
        const newDiv = document.createElement('div');
        newDiv.className = 'input-group mb-2 code-input-group';
        newDiv.innerHTML = `
            <input type="text" name="release_codes[]" class="form-control release-code-input"
                   placeholder="Enter release code (e.g., CLASS2025)">
            <button type="button" class="btn btn-outline-danger remove-code-btn">
                <i class="bi bi-trash"></i>
            </button>
        `;
        container.appendChild(newDiv);

        // Show all remove buttons if more than 1
        document.querySelectorAll('.remove-code-btn').forEach(btn => {
            btn.style.display = 'inline-flex';
        });

        // Add remove functionality
        newDiv.querySelector('.remove-code-btn').addEventListener('click', function() {
            newDiv.remove();
            if (document.querySelectorAll('.code-input-group').length === 1) {
                document.querySelector('.remove-code-btn').style.display = 'none';
            }
        });
    });

    // Initialize remove buttons
    function initRemoveButtons() {
        const removeBtns = document.querySelectorAll('.remove-code-btn');
        if (removeBtns.length === 1) {
            removeBtns[0].style.display = 'none';
        } else {
            removeBtns.forEach(btn => {
                btn.style.display = 'inline-flex';
                btn.addEventListener('click', function() {
                    this.closest('.code-input-group').remove();
                    if (document.querySelectorAll('.code-input-group').length === 1) {
                        document.querySelector('.remove-code-btn').style.display = 'none';
                    }
                });
            });
        }
    }
    initRemoveButtons();

    // Category toggle (existing)
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
</script>
@endpush
