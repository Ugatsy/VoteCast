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
        <form method="POST" action="{{ route('admin.sessions.store') }}">
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

            {{-- Department target --}}
            <div class="mb-3 d-none" id="deptField">
                <label class="form-label fw-semibold small">Target Department</label>
                <input type="text" name="target_department" class="form-control"
                       value="{{ old('target_department') }}" placeholder="e.g. CICT">
            </div>

            {{-- Options --}}
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
                               id="releaseCode" value="1" @checked(old('requires_release_code'))>
                        <label class="form-check-label small" for="releaseCode">Require release code to vote</label>
                    </div>
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
    const sel = document.getElementById('categorySelect');
    const courseF = document.getElementById('courseField');
    const deptF   = document.getElementById('deptField');

    function toggleFields() {
        courseF.classList.add('d-none');
        deptF.classList.add('d-none');
        if (sel.value === 'course')     courseF.classList.remove('d-none');
        if (sel.value === 'department') deptF.classList.remove('d-none');
    }

    sel.addEventListener('change', toggleFields);
    toggleFields();
</script>
@endpush
