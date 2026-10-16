<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolClassRequest;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYears = SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get();
        $yearId = (int) ($request->input('school_year_id') ?: $schoolYears->firstWhere('aktif', true)?->id);
        $classes = SchoolClass::with('schoolYear')->where('institution_id', $institutionId)->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))->orderBy('tingkat')->orderBy('nama_kelas')->paginate(15)->withQueryString();

        return view('classes.index', compact('classes', 'schoolYears', 'yearId'));
    }

    public function create(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYears = SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get();

        return view('classes.create', compact('schoolYears'));
    }

    public function store(SchoolClassRequest $request): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYear = SchoolYear::where('institution_id', $institutionId)->findOrFail($request->integer('school_year_id'));
        $data = $request->validated();
        $data['institution_id'] = $institutionId;
        $data['aktif'] = $request->boolean('aktif');
        $data['school_year_id'] = $schoolYear->id;
        SchoolClass::create($data);

        return to_route('classes.index', ['school_year_id' => $schoolYear->id])->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Request $request, SchoolClass $class): View
    {
        $institutionId = $this->activeInstitutionId($request);
        abort_unless((int) $class->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke kelas ini.');
        $schoolYears = SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get();

        return view('classes.edit', ['schoolClass' => $class, 'schoolYears' => $schoolYears]);
    }

    public function update(SchoolClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        abort_unless((int) $class->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke kelas ini.');
        $schoolYear = SchoolYear::where('institution_id', $institutionId)->findOrFail($request->integer('school_year_id'));
        $data = $request->validated();
        $data['aktif'] = $request->boolean('aktif');
        $data['school_year_id'] = $schoolYear->id;
        $class->update($data);

        return to_route('classes.index', ['school_year_id' => $schoolYear->id])->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        abort_unless((int) $class->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke kelas ini.');

        if ($class->students()->exists()) {
            return back()->withErrors(['class' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.']);
        }

        $class->delete();

        return to_route('classes.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
