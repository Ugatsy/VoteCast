@extends('layouts.admin')
@section('title', 'Enrollment Management')

@section('content')

{{-- Active Semester Card --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-calendar3 me-2 text-primary"></i>Active Semester</strong>
        <span class="badge bg-primary ms-2">{{ $currentSemester }} {{ $currentAcademicYear }}</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.enrollment.semester') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Semester</label>
                <select name="semester" class="form-select">
                    <option value="1st Semester" @selected($currentSemester === '1st Semester')>1st Semester</option>
                    <option value="2nd Semester" @selected($currentSemester === '2nd Semester')>2nd Semester</option>
                    <option value="Summer"       @selected($currentSemester === 'Summer')>Summer</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Academic Year</label>
                <input type="text" name="academic_year" class="form-control"
                       value="{{ $currentAcademicYear }}" placeholder="e.g. 2025-2026" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-check2 me-1"></i>Set Active Semester
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Upload Form --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Upload Enrollment Excel</strong>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.enrollment.upload') }}"
              enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Semester</label>
                <select name="semester" class="form-select" required>
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Academic Year</label>
                <input type="text" name="academic_year" class="form-control"
                       value="{{ $currentAcademicYear }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Excel File (.xlsx)</label>
                <input type="file" name="excel_file" class="form-control"
                       accept=".xlsx,.xls" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success w-100">
                    <i class="bi bi-upload me-1"></i>Upload
                </button>
            </div>
        </form>
        <div class="mt-3 p-3 bg-light rounded small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Expected format:</strong>
            Row 1–2 = School info · Row 4 = "Enrollment List" · Row 5 = Period · Row 6 = Course · Row 8 = Column headers ·
            <strong>Row 9+ = Student data</strong> (No, Code, Last Name, First Name, Middle Name, Sex, Course, Year, Units, Section)
        </div>
    </div>
</div>

{{-- Upload History --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-clock-history me-2"></i>Upload History</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Filename</th>
                    <th>Semester</th>
                    <th>Imported</th>
                    <th>Skipped</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
            <tr>
                <td class="small text-truncate" style="max-width:200px">
                    <i class="bi bi-file-earmark-excel text-success me-1"></i>{{ $batch->filename }}
                </td>
                <td class="small">{{ $batch->semester }} {{ $batch->academic_year }}</td>
                <td><span class="badge bg-success">{{ $batch->imported_records }} imported</span></td>
                <td>
                    @if($batch->skipped_records > 0)
                        <span class="badge bg-warning text-dark">{{ $batch->skipped_records }} skipped</span>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
                <td class="small">{{ $batch->uploader->full_name ?? '—' }}</td>
                <td class="small text-muted">{{ $batch->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No uploads yet.</td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Current Enrollment List --}}
<div class="card border-0 shadow-sm" style="border-radius:10px">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-people me-2"></i>Current Enrollment</strong>
        <span class="badge bg-primary">{{ $enrollments->total() }} students</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student Code</th>
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
                <td><code class="text-primary">{{ $e->student_code }}</code></td>
                <td>{{ $e->full_name }}</td>
                <td>{{ $e->sex === 'F' ? '♀ F' : '♂ M' }}</td>
                <td><span class="badge bg-light text-dark border">{{ $e->course }}</span></td>
                <td>Year {{ $e->year_level }}</td>
                <td>{{ $e->section }}</td>
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
    <div class="card-footer bg-white">
        {{ $enrollments->links() }}
    </div>
    @endif
</div>
@endsection
