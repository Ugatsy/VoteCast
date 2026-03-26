<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EnrollmentImport;
use App\Models\Enrollment;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function index()
    {
        $batches     = UploadBatch::with('uploader')->latest()->get();
        $enrollments = Enrollment::current()->orderBy('course')->orderBy('section')->paginate(50);

        $currentSemester     = session('current_semester', '1st Semester');
        $currentAcademicYear = session('current_academic_year', date('Y') . '-' . (date('Y') + 1));

        return view('admin.enrollment.index', compact(
            'batches',
            'enrollments',
            'currentSemester',
            'currentAcademicYear'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file'    => 'required|file|mimes:xlsx,xls|max:10240',
            'semester'      => 'required|string|max:50',
            'academic_year' => 'required|string|max:20',
        ]);

        $file     = $request->file('excel_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('uploads/enrollment', $filename);

        // Create batch record first so the importer can reference it
        $batch = UploadBatch::create([
            'filename'       => $filename,
            'semester'       => $request->semester,
            'academic_year'  => $request->academic_year,
            'total_records'  => 0,
            'uploaded_by'    => auth()->id(),
        ]);

        // Run the importer
        $import = new EnrollmentImport($batch, $request->semester, $request->academic_year);
        Excel::import($import, $file);

        // Update total count
        $total = $import->getImported() + $import->getSkipped();
        $batch->update(['total_records' => $total]);

        // Set active semester in session
        session([
            'current_semester'      => $request->semester,
            'current_academic_year' => $request->academic_year,
        ]);

        $msg = "Import complete! {$import->getImported()} students imported";
        if ($import->getSkipped() > 0) {
            $msg .= ", {$import->getSkipped()} skipped";
        }

        return redirect()->route('admin.enrollment.index')->with('success', $msg . '.');
    }

    public function setSemester(Request $request)
    {
        $request->validate([
            'semester'      => 'required|string|max:50',
            'academic_year' => 'required|string|max:20',
        ]);

        session([
            'current_semester'      => $request->semester,
            'current_academic_year' => $request->academic_year,
        ]);

        return back()->with('success', 'Active semester updated to ' . $request->semester . ' ' . $request->academic_year . '.');
    }
}
