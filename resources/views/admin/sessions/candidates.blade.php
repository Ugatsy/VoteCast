@extends('layouts.admin')
@section('title', 'Manage Candidates — ' . $votingSession->title)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0 small">
            <a href="{{ route('admin.sessions.show', $votingSession) }}">← Back to election</a>
        </p>
    </div>
    <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-eye me-1"></i>View Election
    </a>
</div>

<div class="row g-4">

    {{-- Left: Add Position --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
            <div class="card-header bg-white py-3">
                <strong><i class="bi bi-plus-circle me-2 text-primary"></i>Add Position</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sessions.positions.add', $votingSession) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. President" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Max Winners</label>
                        <input type="number" name="max_winners" class="form-control" value="1" min="1" max="10">
                    </div>
                    <button class="btn btn-primary w-100">Add Position</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Positions & Candidates --}}
    <div class="col-lg-8">
        @forelse($votingSession->positions as $position)
        <div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <strong>{{ $position->title }}</strong>
                <div class="d-flex gap-2 align-items-center">
                    <span class="text-muted small">{{ $position->candidates->count() }} candidate(s)</span>
                    <form method="POST" action="{{ route('admin.positions.delete', $position) }}"
                          onsubmit="return confirm('Delete this position and all its candidates?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Existing candidates --}}
            @if($position->candidates->count())
            <div class="px-3 pt-3">
                <div class="row g-2 mb-3">
                    @foreach($position->candidates as $candidate)
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $candidate->photo_url }}"
                                     style="width:40px;height:40px;border-radius:50%;object-fit:cover" alt="">
                                <div>
                                    <div class="small fw-medium">{{ $candidate->student->full_name }}</div>
                                    <div class="text-muted" style="font-size:0.75rem">{{ $candidate->student->section }}</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.candidates.delete', $candidate) }}"
                                  onsubmit="return confirm('Remove this candidate?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0 px-1">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Add candidate form --}}
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('admin.positions.candidates.add', $position) }}"
                      enctype="multipart/form-data" class="candidate-form">
                    @csrf

                    {{-- Hidden field that holds the selected student_id on submit --}}
                    <input type="hidden" name="student_id" class="student-id-input">

                    <div class="row g-2 align-items-end">

                        {{-- Searchable student picker --}}
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Select Student</label>
                            <div class="student-picker position-relative">
                                <input
                                    type="text"
                                    class="form-control form-control-sm student-search"
                                    placeholder="Type name or ID…"
                                    autocomplete="off"
                                >
                                <div class="student-dropdown border rounded bg-white shadow-sm"
                                     style="display:none; position:absolute; z-index:1050;
                                            width:100%; max-height:200px; overflow-y:auto;
                                            top:calc(100% + 2px); left:0;">
                                    @foreach($students as $student)
                                    <div class="student-option px-3 py-2"
                                         style="cursor:pointer; font-size:0.85rem; line-height:1.3;"
                                         data-id="{{ $student->id }}"
                                         data-label="{{ $student->full_name }} ({{ $student->student_id }})">
                                        <span class="fw-medium">{{ $student->full_name }}</span>
                                        <span class="text-muted ms-1 small">{{ $student->student_id }}</span>
                                        @if($student->section ?? null)
                                            <span class="text-muted small"> · {{ $student->section }}</span>
                                        @endif
                                    </div>
                                    @endforeach
                                    <div class="student-no-results px-3 py-2 text-muted small"
                                         style="display:none;">No students found.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Photo</label>
                            <input type="file" name="photo" class="form-control form-control-sm"
                                   accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Manifesto</label>
                            <input type="text" name="manifesto" class="form-control form-control-sm"
                                   placeholder="Short platform">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-success btn-sm w-100">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card border-0 shadow-sm p-4 text-center text-muted" style="border-radius:10px">
            <i class="bi bi-ballot d-block fs-1 mb-2 opacity-25"></i>
            No positions yet. Add your first position on the left.
        </div>
        @endforelse
    </div>

</div>

<div class="mt-3">
    <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-primary">
        Done — View Election →
    </a>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.student-picker').forEach(picker => {
    const searchInput  = picker.querySelector('.student-search');
    const dropdown     = picker.querySelector('.student-dropdown');
    const options      = picker.querySelectorAll('.student-option');
    const noResults    = picker.querySelector('.student-no-results');
    const hiddenInput  = picker.closest('form').querySelector('.student-id-input');

    // Show dropdown when the text input is focused
    searchInput.addEventListener('focus', () => {
        filterOptions('');
        dropdown.style.display = 'block';
    });

    // Live-filter as user types
    searchInput.addEventListener('input', () => {
        // Clear any previously selected student when the user types again
        hiddenInput.value = '';
        searchInput.classList.remove('is-valid');
        filterOptions(searchInput.value.trim());
        dropdown.style.display = 'block';
    });

    // Select a student on click
    options.forEach(opt => {
        opt.addEventListener('mousedown', e => {
            e.preventDefault(); // keep focus on input
            selectStudent(opt);
        });
        // Hover highlight
        opt.addEventListener('mouseenter', () => opt.style.background = '#f0f0f0');
        opt.addEventListener('mouseleave', () => opt.style.background = '');
    });

    // Close dropdown when clicking anywhere else
    document.addEventListener('click', e => {
        if (!picker.contains(e.target)) dropdown.style.display = 'none';
    });

    // Guard: prevent submit without a selection
    picker.closest('form').addEventListener('submit', e => {
        if (!hiddenInput.value) {
            e.preventDefault();
            searchInput.focus();
            searchInput.classList.add('is-invalid');
        }
    });

    function filterOptions(query) {
        const q = query.toLowerCase();
        let visible = 0;
        options.forEach(opt => {
            const matches = opt.dataset.label.toLowerCase().includes(q);
            opt.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });
        noResults.style.display = visible === 0 ? '' : 'none';
    }

    function selectStudent(opt) {
        hiddenInput.value      = opt.dataset.id;
        searchInput.value      = opt.dataset.label;
        searchInput.classList.remove('is-invalid');
        searchInput.classList.add('is-valid');
        dropdown.style.display = 'none';
    }
});
</script>
@endpush
