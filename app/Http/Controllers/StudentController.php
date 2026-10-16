<?php

namespace App\Http\Controllers;

use App\Exports\StudentExport;
use App\Exports\StudentTemplateExport;
use App\Http\Requests\StudentRequest;
use App\Imports\StudentImport;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Throwable;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $search = trim((string) $request->string('search'));
        $students = Student::with(['schoolClass', 'schoolYear'])
            ->where('institution_id', $institutionId)
            ->when($search !== '', fn ($query) => $query->where(fn ($subQuery) => $subQuery
                ->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%'.strtolower($search).'%'])
                ->orWhereRaw('LOWER(nis) LIKE ?', ['%'.strtolower($search).'%'])))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when($request->filled('school_year_id'), fn ($query) => $query->where('school_year_id', $request->integer('school_year_id')))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('aktif', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('aktif', false))
            ->orderBy('nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'classes' => SchoolClass::where('institution_id', $institutionId)->where('aktif', true)->orderBy('nama_kelas')->get(),
            'schoolYears' => SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYear = SchoolYear::active()->where('institution_id', $institutionId)->first();
        $classes = $schoolYear ? SchoolClass::active()->where('institution_id', $institutionId)->where('school_year_id', $schoolYear->id)->orderBy('nama_kelas')->get() : collect();

        return view('students.create', compact('schoolYear', 'classes'));
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYear = SchoolYear::active()->where('institution_id', $institutionId)->first();
        if (! $schoolYear) {
            return back()->withErrors(['school_year' => 'Belum ada tahun ajaran aktif.'])->withInput();
        }
        $this->ensureClassInYear($request->integer('class_id'), $institutionId, $schoolYear->id);
        $data = $request->validated();
        $data['institution_id'] = $institutionId;
        $data['school_year_id'] = $schoolYear->id;
        $data['aktif'] = $request->boolean('aktif');
        Student::create($data);

        return to_route('students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Request $request, Student $student): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $this->ensureStudentTenant($student, $institutionId);
        $classes = SchoolClass::active()->where('institution_id', $institutionId)->where('school_year_id', $student->school_year_id)->orderBy('nama_kelas')->get();

        return view('students.edit', compact('student', 'classes'));
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        $this->ensureStudentTenant($student, $institutionId);
        $this->ensureClassInYear($request->integer('class_id'), $institutionId, $student->school_year_id);
        $data = $request->validated();
        $data['aktif'] = $request->boolean('aktif');
        $student->update($data);

        return to_route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->ensureStudentTenant($student, $this->activeInstitutionId($request));
        $student->delete();

        return to_route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    public function export(Request $request): mixed
    {
        $filters = $request->only(['search', 'class_id', 'school_year_id', 'status']);
        $format = $request->input('format') === 'csv' ? Excel::CSV : Excel::XLSX;
        $extension = $format === Excel::CSV ? 'csv' : 'xlsx';

        return ExcelFacade::download(new StudentExport($this->activeInstitutionId($request), $filters), "data-siswa.{$extension}", $format);
    }

    public function downloadTemplate(): mixed
    {
        return ExcelFacade::download(new StudentTemplateExport(), 'template-import-siswa.xlsx');
    }

    public function importForm(): View
    {
        return view('students.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']], ['required' => 'File Excel wajib dipilih.', 'mimes' => 'File harus berformat XLSX, XLS, atau CSV.', 'max' => 'Ukuran file maksimal 5 MB.']);
        $institutionId = $this->activeInstitutionId($request);
        $schoolYear = SchoolYear::active()->where('institution_id', $institutionId)->first();
        if (! $schoolYear) {
            return back()->withErrors(['school_year' => 'Belum ada tahun ajaran aktif.']);
        }

        try {
            $import = new StudentImport($institutionId, $schoolYear->id);
            ExcelFacade::import($import, $request->file('file'));
            $message = "Berhasil: {$import->added()} siswa ditambah, {$import->updated()} siswa diperbarui, {$import->failed()} baris gagal.";

            return to_route('students.index')->with('success', $message);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['file' => 'File tidak dapat diproses. Pastikan format template sudah benar.']);
        }
    }

    private function ensureStudentTenant(Student $student, int $institutionId): void
    {
        abort_unless((int) $student->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke data siswa ini.');
    }

    private function ensureClassInYear(int $classId, int $institutionId, int $schoolYearId): void
    {
        abort_unless(SchoolClass::whereKey($classId)->where('institution_id', $institutionId)->where('school_year_id', $schoolYearId)->exists(), 422, 'Kelas tidak sesuai dengan tahun ajaran aktif.');
    }
}
