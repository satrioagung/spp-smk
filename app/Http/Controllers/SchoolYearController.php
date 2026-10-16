<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolYearRequest;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);

        return view('school-years.index', ['schoolYears' => SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->paginate(15)]);
    }

    public function create(): View
    {
        return view('school-years.create');
    }

    public function store(SchoolYearRequest $request): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        $data = $request->validated();
        $data['institution_id'] = $institutionId;
        $data['aktif'] = $request->boolean('aktif');

        DB::transaction(function () use ($institutionId, $data): void {
            if ($data['aktif']) {
                SchoolYear::where('institution_id', $institutionId)->update(['aktif' => false]);
            }
            SchoolYear::create($data);
        });

        return to_route('school-years.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(Request $request, SchoolYear $schoolYear): View
    {
        $this->ensureTenant($request, $schoolYear);

        return view('school-years.edit', compact('schoolYear'));
    }

    public function update(SchoolYearRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        abort_unless((int) $schoolYear->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke tahun ajaran ini.');
        $data = $request->validated();
        $data['aktif'] = $request->boolean('aktif');

        DB::transaction(function () use ($institutionId, $schoolYear, $data): void {
            if ($data['aktif']) {
                SchoolYear::where('institution_id', $institutionId)->where('id', '<>', $schoolYear->id)->update(['aktif' => false]);
            }
            $schoolYear->update($data);
        });

        return to_route('school-years.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function setAktif(Request $request, SchoolYear $schoolYear): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        abort_unless((int) $schoolYear->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke tahun ajaran ini.');

        DB::transaction(function () use ($institutionId, $schoolYear): void {
            SchoolYear::where('institution_id', $institutionId)->update(['aktif' => false]);
            $schoolYear->update(['aktif' => true]);
        });

        return to_route('school-years.index')->with('success', "Tahun ajaran {$schoolYear->nama} sekarang aktif.");
    }

    public function destroy(Request $request, SchoolYear $schoolYear): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        abort_unless((int) $schoolYear->institution_id === $institutionId, 403, 'Anda tidak memiliki akses ke tahun ajaran ini.');

        if (Schema::hasTable('bills') && Schema::hasColumn('bills', 'school_year_id') && DB::table('bills')->where('school_year_id', $schoolYear->id)->exists()) {
            return back()->withErrors(['school_year' => 'Tahun ajaran tidak dapat dihapus karena sudah memiliki tagihan.']);
        }

        $schoolYear->delete();

        return to_route('school-years.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    private function ensureTenant(Request $request, SchoolYear $schoolYear): void
    {
        abort_unless((int) $schoolYear->institution_id === $this->activeInstitutionId($request), 403, 'Anda tidak memiliki akses ke tahun ajaran ini.');
    }
}
