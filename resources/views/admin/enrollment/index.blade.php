@extends('layouts.admin')
@section('title', 'Enrollment Management')

@section('content')

{{-- Tip --}}
<div class="vc-tip vc-tip--yellow mb-4">
    <i class="bi bi-lightbulb-fill text-warning me-2 flex-shrink-0 mt-1"></i>
    <div class="small">
        <strong>Before creating an election:</strong> Set the active semester below, then upload the enrollment Excel file.
        The system uses enrollment data to determine who is eligible to vote.
        Expected Excel format: <strong>Row 5</strong> = Period · <strong>Row 6</strong> = Course · <strong>Row 9+</strong> = Student data.
    </div>
</div>

{{-- Active Semester --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2">
        <strong><i class="bi bi-calendar3 me-2 text-primary"></i>Active Semester</strong>
        @php $activeSemester = App\Models\Semester::getCurrent(); @endphp
        @if($activeSemester)
            <span class="badge bg-success">{{ $activeSemester->name }} {{ $activeSemester->academic_year ?? $currentAcademicYear }}</span>
        @else
            <span class="badge bg-warning text-dark">None set</span>
        @endif
    </div>
    <div class="card-body px-4">
        <form method="POST" action="{{ route('admin.enrollment.semester') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Semester</label>
                <select name="semester" class="form-select">
                    <option value="1st Semester" @selected(($activeSemester && $activeSemester->name === '1st Semester') || $currentSemester === '1st Semester')>1st Semester</option>
                    <option value="2nd Semester" @selected(($activeSemester && $activeSemester->name === '2nd Semester') || $currentSemester === '2nd Semester')>2nd Semester</option>
                    <option value="Summer"       @selected(($activeSemester && $activeSemester->name === 'Summer') || $currentSemester === 'Summer')>Summer</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Academic Year</label>
                <input type="text" name="academic_year" class="form-control"
                       value="{{ $activeSemester->academic_year ?? $currentAcademicYear }}"
                       placeholder="e.g. 2025-2026" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-check2 me-1"></i>Set Active Semester
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Upload --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-header bg-white py-3 px-4">
        <strong><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Upload Enrollment Excel</strong>
    </div>
    <div class="card-body px-4">
        <form method="POST" action="{{ route('admin.enrollment.upload') }}"
              enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="semester"      value="{{ $currentSemester }}">
            <input type="hidden" name="academic_year" value="{{ $currentAcademicYear }}">
            <div class="col-md-9">
                <label class="form-label small fw-semibold">Excel File (.xlsx or .xls)</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success w-100">
                    <i class="bi bi-upload me-1"></i>Upload
                </button>
            </div>
        </form>
        <div class="vc-tip vc-tip--gray mt-3">
            <i class="bi bi-file-earmark-text me-2 text-muted flex-shrink-0"></i>
            <span class="small text-muted">
                Uploading for: <strong>{{ $currentSemester }} {{ $currentAcademicYear }}</strong>
                &nbsp;·&nbsp; Columns: No, Code, Last Name, First Name, Middle, Sex, Course, Year, Units, Section
            </span>
        </div>
    </div>
</div>

{{-- Upload History --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-header bg-white py-3 px-4">
        <strong><i class="bi bi-clock-history me-2"></i>Upload History</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">File</th>
                    <th>Semester</th>
                    <th>Imported</th>
                    <th>Skipped</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
            @php
                $isActiveBatch = ($batch->semester === $currentSemester && $batch->academic_year === $currentAcademicYear && $batch->imported_records > 0);
            @endphp
            <tr class="{{ $isActiveBatch ? 'table-primary' : '' }}">
                <td class="ps-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-excel text-success"></i>
                        <span class="small text-truncate" style="max-width:180px">{{ $batch->filename }}</span>
                        @if($isActiveBatch)
                            <span class="badge bg-primary" style="font-size:0.7rem">Active</span>
                        @endif
                    </div>
                    @if($batch->courses && count($batch->courses) > 0)
                    <div class="mt-1">
                        @foreach($batch->courses as $course)
                            <span class="badge bg-light text-dark border me-1" style="font-size:0.7rem">{{ $course }}</span>
                        @endforeach
                    </div>
                    @endif
                </td>
                <td class="small">{{ $batch->semester }}<br><span class="text-muted">{{ $batch->academic_year }}</span></td>
                <td><span class="badge bg-success">{{ $batch->imported_records }}</span></td>
                <td>
                    @if($batch->skipped_records > 0)
                        <span class="badge bg-warning text-dark">{{ $batch->skipped_records }}</span>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
                <td class="small text-muted">{{ $batch->uploader->full_name ?? '—' }}</td>
                <td class="small text-muted">{{ $batch->created_at->format('M d, Y') }}</td>
                <td class="text-end pe-4">
                    @if($batch->imported_records > 0)
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.enrollment.batches.show', $batch) }}" class="btn btn-outline-primary" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.enrollment.batches.destroy', $batch) }}"
                              onsubmit="return confirm('Delete this batch? All {{ $batch->imported_records }} enrollments will be removed.')"
                              class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">No uploads yet.</td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Current Enrollment List --}}
<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-people me-2"></i>Current Enrollment</strong>
        <span class="badge bg-primary rounded-pill">{{ $enrollments->total() }} students</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Student Code</th>
                    <th>Full Name</th>
                    <th>Sex</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
            @forelse($enrollments as $e)
            <tr>
                <td class="ps-4"><code class="text-primary">{{ $e->student_code }}</code></td>
                <td>{{ $e->full_name }}</td>
                <td class="text-muted small">{{ $e->sex === 'F' ? '♀ F' : '♂ M' }}</td>
                <td><span class="badge bg-light text-dark border">{{ $e->course }}</span></td>
                <td class="small text-muted">Yr {{ $e->year_level }}</td>
                <td class="small text-muted">{{ $e->section }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    No enrollment data for the current semester.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($enrollments->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 px-4">
        <small class="text-muted">Showing {{ $enrollments->firstItem() }}–{{ $enrollments->lastItem() }} of {{ $enrollments->total() }}</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                @if($enrollments->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">Prev</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $enrollments->previousPageUrl() }}">Prev</a></li>
                @endif
                @foreach($enrollments->getUrlRange(1, $enrollments->lastPage()) as $page => $url)
                    <li class="page-item {{ $page == $enrollments->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach
                @if($enrollments->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $enrollments->nextPageUrl() }}">Next</a></li>
                @else
                    <li class="page-item disabled"><span class="page-link">Next</span></li>
                @endif
            </ul>
        </nav>
    </div>
    @endif
</div>

<style>
.vc-tip { border-radius: 10px; padding: .75rem 1rem; display: flex; align-items: flex-start; }
.vc-tip--yellow { background: #fffbeb; border: 1px solid #fde68a; }
.vc-tip--gray   { background: #f8fafc; border: 1px solid #e2e8f0; }
</style>
@endsection

