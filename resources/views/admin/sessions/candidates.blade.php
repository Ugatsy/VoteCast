@extends('layouts.admin')
@section('title', 'Manage Candidates — ' . $votingSession->title)

@section('content')

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.sessions.show', $votingSession) }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to election
        </a>
        <h5 class="mb-0 fw-bold mt-1">{{ $votingSession->title }}</h5>
        <span class="badge badge-status-{{ $votingSession->status }}">{{ ucfirst($votingSession->status) }}</span>
    </div>
    <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-eye me-1"></i>View Election
    </a>
</div>

{{-- Tip --}}
<div class="vc-tip vc-tip--blue mb-4">
    <i class="bi bi-lightbulb-fill text-primary me-2 flex-shrink-0"></i>
    <div class="small">
        <strong>How to add candidates:</strong>
        First add a <strong>position</strong> (e.g. President, Vice President) on the left, then search and add students to that position.
        Students should update their own <strong>photo and manifesto</strong> from their Profile page before the election goes live.
    </div>
</div>

<div class="row g-4">

    {{-- Left: Add Position --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-header bg-white py-3 px-4">
                <strong><i class="bi bi-plus-circle me-2 text-primary"></i>Add Position</strong>
            </div>
            <div class="card-body px-4">
                <form method="POST" action="{{ route('admin.sessions.positions.add', $votingSession) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. President" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="description" class="form-control" placeholder="Brief role description">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Max Winners</label>
                        <input type="number" name="max_winners" class="form-control" value="1" min="1" max="10">
                        <div class="form-text">How many candidates can win this position.</div>
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-plus me-1"></i>Add Position
                    </button>
                </form>
            </div>
        </div>

        {{-- Positions summary --}}
        @if($votingSession->positions->count())
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-header bg-white py-3 px-4">
                <strong class="small">Positions Added ({{ $votingSession->positions->count() }})</strong>
            </div>
            <div class="list-group list-group-flush">
                @foreach($votingSession->positions as $p)
                <div class="list-group-item px-4 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small fw-medium">{{ $p->title }}</div>
                        <div class="text-muted" style="font-size:0.73rem">{{ $p->candidates->count() }} candidate(s)</div>
                    </div>
                    <span class="badge bg-primary rounded-pill">{{ $p->candidates->count() }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Positions & Candidates --}}
    <div class="col-lg-8">
        @forelse($votingSession->positions as $position)
        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $position->title }}</strong>
                    @if($position->description)
                        <div class="text-muted small">{{ $position->description }}</div>
                    @endif
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-secondary border small">{{ $position->candidates->count() }}/{{ $position->max_winners }} winner(s)</span>
                    <form method="POST" action="{{ route('admin.positions.delete', $position) }}"
                          onsubmit="return confirm('Delete this position and all its candidates?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Delete position">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Existing candidates --}}
            @if($position->candidates->count())
            <div class="px-4 pt-3">
                <div class="row g-2 mb-3">
                    @foreach($position->candidates as $candidate)
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded-3 bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $candidate->photo_url }}"
                                     style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0" alt="">
                                <div>
                                    <div class="small fw-semibold lh-1">{{ $candidate->student->full_name }}</div>
                                    <div class="text-muted" style="font-size:0.72rem">{{ $candidate->student->section }}</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.candidates.delete', $candidate) }}"
                                  onsubmit="return confirm('Remove this candidate?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0 px-1 border-0" title="Remove">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Add candidate --}}
            <div class="card-body px-4 pt-0 pb-3">
                <form method="POST" action="{{ route('admin.positions.candidates.add', $position) }}"
                      class="candidate-form">
                    @csrf
                    <input type="hidden" name="student_id" class="student-id-input">

                    <div class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-semibold mb-1">Add Student</label>
                            <div class="student-picker position-relative">
                                <input type="text"
                                       class="form-control form-control-sm student-search"
                                       placeholder="Search by name or student ID…"
                                       autocomplete="off">
                                <div class="student-dropdown border rounded-3 bg-white shadow"
                                     style="display:none;position:absolute;z-index:1050;width:100%;max-height:200px;overflow-y:auto;top:calc(100% + 3px);left:0;">
                                    @foreach($students as $student)
                                    <div class="student-option px-3 py-2"
                                         style="cursor:pointer;font-size:0.85rem;line-height:1.3;"
                                         data-id="{{ $student->id }}"
                                         data-label="{{ $student->full_name }} ({{ $student->student_id }})">
                                        <span class="fw-medium">{{ $student->full_name }}</span>
                                        <span class="text-muted ms-1 small">{{ $student->student_id }}</span>
                                        @if($student->section ?? null)
                                            <span class="text-muted small"> · {{ $student->section }}</span>
                                        @endif
                                    </div>
                                    @endforeach
                                    <div class="student-no-results px-3 py-2 text-muted small" style="display:none;">No students found.</div>
                                </div>
                            </div>
                        </div>
                        <div class="pb-0" style="padding-bottom:1px">
                            <button class="btn btn-success btn-sm px-3">
                                <i class="bi bi-plus me-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <div class="form-text mt-1">
                        <i class="bi bi-info-circle me-1"></i>
                        Photo & manifesto are managed by the student from their profile page.
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card border-0 shadow-sm p-5 text-center text-muted" style="border-radius:12px">
            <i class="bi bi-ballot d-block fs-1 mb-2 opacity-25"></i>
            <div class="fw-medium mb-1">No positions yet</div>
            <div class="small">Add your first position using the form on the left.</div>
        </div>
        @endforelse

        @if($votingSession->positions->count())
        <div class="mt-2">
            <a href="{{ route('admin.sessions.show', $votingSession) }}" class="btn btn-primary">
                Done — View Election →
            </a>
        </div>
        @endif
    </div>

</div>

@include('admin.partials.qr-codes-modal')

<style>
.vc-tip {
    border-radius: 10px;
    padding: .75rem 1rem;
    display: flex;
    align-items: flex-start;
}
.vc-tip--blue { background:#eff6ff; border: 1px solid #bfdbfe; }
</style>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.student-picker').forEach(picker => {
    const searchInput = picker.querySelector('.student-search');
    const dropdown    = picker.querySelector('.student-dropdown');
    const options     = picker.querySelectorAll('.student-option');
    const noResults   = picker.querySelector('.student-no-results');
    const hiddenInput = picker.closest('form').querySelector('.student-id-input');

    searchInput.addEventListener('focus', () => { filterOptions(''); dropdown.style.display = 'block'; });
    searchInput.addEventListener('input', () => {
        hiddenInput.value = '';
        searchInput.classList.remove('is-valid');
        filterOptions(searchInput.value.trim());
        dropdown.style.display = 'block';
    });

    options.forEach(opt => {
        opt.addEventListener('mousedown', e => { e.preventDefault(); selectStudent(opt); });
        opt.addEventListener('mouseenter', () => opt.style.background = '#f0f4ff');
        opt.addEventListener('mouseleave', () => opt.style.background = '');
    });

    document.addEventListener('click', e => { if (!picker.contains(e.target)) dropdown.style.display = 'none'; });

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
        hiddenInput.value = opt.dataset.id;
        searchInput.value = opt.dataset.label;
        searchInput.classList.remove('is-invalid');
        searchInput.classList.add('is-valid');
        dropdown.style.display = 'none';
    }
});
</script>
@endpush

