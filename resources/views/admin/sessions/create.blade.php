@extends('layouts.admin')
@section('title', 'Create Election')

@section('content')

<div class="row justify-content-center">
<div class="col-lg-7">

    <div class="mb-3">
        <a href="{{ route('admin.sessions.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to Elections
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <strong><i class="bi bi-plus-circle me-2 text-primary"></i>New Election</strong>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.sessions.store') }}" id="electionForm">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Election Title <span class="text-danger">*</span></label>
                    <input type="text" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}"
                           placeholder="e.g. BSIT Student Council Election 2025" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Description <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Brief details about this election">{{ old('description') }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Start Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" id="startDate"
                               class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date') }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">End Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" id="endDate"
                               class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date') }}" required>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Who Can Vote? <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" id="categorySelect" required>
                        <option value="">— Select eligibility —</option>
                        <option value="course"     @selected(old('category') === 'course')>By Course</option>
                        <option value="section"    @selected(old('category') === 'section')>By Section</option>
                        <option value="department" @selected(old('category') === 'department')>By Department</option>
                        <option value="manual"     @selected(old('category') === 'manual')>Manual</option>
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
                </div>

                <div class="mb-3 d-none" id="deptField">
                    <label class="form-label fw-semibold small">Target Department</label>
                    <input type="text" name="target_department" class="form-control"
                           value="{{ old('target_department') }}" placeholder="e.g. CICT">
                </div>

                <hr class="my-4">

                <p class="fw-semibold small mb-3">Election Options</p>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_release_code"
                               id="releaseCodeToggle" value="1" @checked(old('requires_release_code'))>
                        <label class="form-check-label small" for="releaseCodeToggle">
                            Require a release code to vote
                        </label>
                    </div>
                    <div class="form-text text-muted ms-4" style="font-size:0.78rem">
                        A unique code will be auto-generated. Students must enter or scan it before voting.
                    </div>
                </div>

                {{-- Release code preview --}}
                <div id="releaseCodePreview" class="mb-4" style="display:none;">
                    <div class="p-3 rounded-3 border" style="background:#f8f9ff;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-semibold text-primary">
                                <i class="bi bi-key me-1"></i>Auto-generated Release Code
                            </span>
                            <button type="button" id="regenCode" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.78rem">
                                <i class="bi bi-arrow-clockwise me-1"></i>Regenerate
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <code id="codePreviewDisplay" class="fs-5 fw-bold px-3 py-1 rounded"
                                  style="background:#e8edff; color:#1a56db; letter-spacing:4px;">——————</code>
                            <span class="text-muted small" id="codeExpiry"></span>
                        </div>
                        <div class="form-text mt-2" style="font-size:0.75rem">
                            <i class="bi bi-info-circle me-1"></i>
                            The QR code for this will be shown after you save the election.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 pt-2">
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
(function () {
    // ── Category toggle ────────────────────────────────────────────────────────
    const sel      = document.getElementById('categorySelect');
    const courseF  = document.getElementById('courseField');
    const sectionF = document.getElementById('sectionField');
    const deptF    = document.getElementById('deptField');

    function toggleCategoryFields() {
        courseF.classList.add('d-none');
        sectionF.classList.add('d-none');
        deptF.classList.add('d-none');
        if (sel.value === 'course')     courseF.classList.remove('d-none');
        if (sel.value === 'section')    sectionF.classList.remove('d-none');
        if (sel.value === 'department') deptF.classList.remove('d-none');
    }
    sel.addEventListener('change', toggleCategoryFields);
    toggleCategoryFields();

    // ── Release code auto-generation ──────────────────────────────────────────
    const toggle      = document.getElementById('releaseCodeToggle');
    const preview     = document.getElementById('releaseCodePreview');
    const display     = document.getElementById('codePreviewDisplay');
    const expiryLabel = document.getElementById('codeExpiry');
    const regenBtn    = document.getElementById('regenCode');
    const endDate     = document.getElementById('endDate');

    // Hidden input carries the generated code to the server
    const hiddenCode = document.createElement('input');
    hiddenCode.type  = 'hidden';
    hiddenCode.name  = 'generated_release_code';
    document.getElementById('electionForm').appendChild(hiddenCode);

    function makeCode() {
        const chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars[Math.floor(Math.random() * chars.length)];
        }
        return code;
    }

    function refreshCode() {
        const code   = makeCode();
        display.textContent = code;
        hiddenCode.value    = code;
    }

    function updateExpiryLabel() {
        if (endDate.value) {
            const d = new Date(endDate.value);
            expiryLabel.textContent = '· expires ' + d.toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } else {
            expiryLabel.textContent = '';
        }
    }

    function applyToggle() {
        if (toggle.checked) {
            preview.style.display = 'block';
            if (!hiddenCode.value) refreshCode();
            updateExpiryLabel();
        } else {
            preview.style.display = 'none';
            hiddenCode.value = '';
        }
    }

    toggle.addEventListener('change', applyToggle);
    regenBtn.addEventListener('click', refreshCode);
    endDate.addEventListener('change', updateExpiryLabel);

    @if(old('requires_release_code'))
        toggle.checked = true;
        applyToggle();
    @endif

})();
</script>
@endpush
