<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillGenerateRequest;
use App\Models\Bill;
use App\Models\PaymentType;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\BillGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BillController extends Controller
{
    public function __construct(private readonly BillGeneratorService $generator)
    {
    }

    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $query = Bill::with(['student.schoolClass', 'paymentType', 'schoolYear'])->forInstitution($institutionId)
            ->when($request->filled('school_year_id'), fn ($query) => $query->where('school_year_id', $request->integer('school_year_id')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('class_id', $request->integer('class_id'))))
            ->when($request->filled('payment_type_id'), fn ($query) => $query->where('payment_type_id', $request->integer('payment_type_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('bulan'), fn ($query) => $query->where('bulan', $request->integer('bulan')))
            ->when(trim((string) $request->string('search')) !== '', fn ($query) => $query->whereHas('student', function ($studentQuery) use ($request): void {
                $search = strtolower(trim((string) $request->string('search')));
                $studentQuery->where(fn ($searchQuery) => $searchQuery->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%{$search}%"])->orWhereRaw('LOWER(nis) LIKE ?', ["%{$search}%"]));
            }))
            ->latest('id');

        $summaryBills = (clone $query)->get(['id', 'status', 'nominal', 'nominal_terbayar']);
        $summary = [
            'total' => $summaryBills->count(),
            'belum_bayar' => $summaryBills->where('status', 'belum_bayar')->count(),
            'sebagian' => $summaryBills->where('status', 'sebagian')->count(),
            'lunas' => $summaryBills->where('status', 'lunas')->count(),
            'tunggakan' => $summaryBills->sum(fn (Bill $bill) => $bill->sisa_tagihan),
        ];

        $groupedBills = $this->groupForDisplay($query->get());

        return view('bills.index', [
            'bills' => $this->paginateGroups($groupedBills),
            'summary' => $summary,
            'schoolYears' => SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get(),
            'classes' => SchoolClass::where('institution_id', $institutionId)->orderBy('nama_kelas')->get(),
            'paymentTypes' => PaymentType::where('institution_id', $institutionId)->orderBy('nama')->get(),
        ]);
    }

    public function generateForm(Request $request): View
    {
        return $this->generateView($request, [], null, []);
    }

    public function preview(BillGenerateRequest $request): View
    {
        $params = $request->validated();
        $params['institution_id'] = $this->activeInstitutionId($request);
        $params['nominal'] = $params['nominal'] ?? PaymentType::findOrFail($params['payment_type_id'])->nominal_default;
        $preview = $this->generator->preview($params);

        return $this->generateView($request, $params, $preview, $params);
    }

    public function generate(BillGenerateRequest $request): RedirectResponse
    {
        $params = $request->validated();
        $params['institution_id'] = $this->activeInstitutionId($request);
        $result = match ($params['target']) {
            'class' => $this->generator->generatePerKelas((int) $params['class_id'], $params),
            'student' => $this->generator->generatePerSiswa((int) $params['student_id'], $params),
            default => $this->generator->generateMassal($params),
        };

        return to_route('bills.index')->with('success', "Berhasil membuat {$result['berhasil']} tagihan, {$result['dilewati']} tagihan dilewati (sudah ada).".($result['gagal'] ? " {$result['gagal']} tagihan gagal." : ''));
    }

    public function show(Request $request, Bill $bill): View
    {
        $this->ensureTenant($request, $bill);
        $bill->load(['student.schoolClass', 'paymentType', 'schoolYear']);
        $paymentHistory = collect();
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'bill_id')) {
            $paymentHistory = Payment::with('bills')->where('institution_id', $bill->institution_id)
                ->where(function ($query) use ($bill): void {
                    $query->where('bill_id', $bill->id)->orWhereHas('bills', fn ($billQuery) => $billQuery->whereKey($bill->id));
                })
                ->latest()
                ->get();
        }
        return view('bills.show', compact('bill', 'paymentHistory'));
    }

    public function destroy(Request $request, Bill $bill): RedirectResponse
    {
        $this->ensureTenant($request, $bill);
        if ($bill->status !== 'belum_bayar') {
            return back()->withErrors(['bill' => 'Tagihan hanya dapat dihapus jika belum dibayar.']);
        }
        $bill->delete();
        return to_route('bills.index')->with('success', 'Tagihan berhasil dihapus.');
    }

    private function generateView(Request $request, array $params, ?array $preview, array $previewParams): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $activeYear = SchoolYear::active()->where('institution_id', $institutionId)->first();
        $selectedYearId = (int) ($previewParams['school_year_id'] ?? $activeYear?->id);
        $schoolYear = SchoolYear::where('institution_id', $institutionId)->find($selectedYearId) ?: $activeYear;
        $paymentTypes = PaymentType::active()->where('institution_id', $institutionId)->where('school_year_id', $schoolYear?->id)->orderBy('nama')->get();

        return view('bills.generate', [
            'paymentTypes' => $paymentTypes,
            'schoolYear' => $schoolYear,
            'classes' => $schoolYear ? SchoolClass::active()->where('institution_id', $institutionId)->where('school_year_id', $schoolYear->id)->orderBy('nama_kelas')->get() : collect(),
            'students' => $schoolYear ? Student::active()->where('institution_id', $institutionId)->where('school_year_id', $schoolYear->id)->with('schoolClass')->orderBy('nama_lengkap')->get() : collect(),
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
            'preview' => $preview,
            'previewParams' => $previewParams,
        ]);
    }

    private function ensureTenant(Request $request, Bill $bill): void
    {
        abort_unless((int) $bill->institution_id === $this->activeInstitutionId($request), 403, 'Anda tidak memiliki akses ke tagihan ini.');
    }

    private function groupForDisplay(Collection $bills): Collection
    {
        return $bills
            ->groupBy('student_id')
            ->map(function (Collection $items): Bill {
                /** @var Bill $row */
                $row = $items->first();
                $studentBills = $items->sortBy(fn (Bill $bill): string => sprintf('%s-%04d-%02d-%08d', $bill->paymentType?->nama ?: '', (int) $bill->tahun, (int) ($bill->bulan ?? 99), (int) $bill->id))->values();
                $row->setRelation('studentBills', $studentBills);
                $row->setAttribute('student_bill_ids', $studentBills->filter(fn (Bill $bill): bool => in_array($bill->status, ['belum_bayar', 'sebagian'], true) && $bill->sisa_tagihan > 0)->pluck('id')->values()->all());
                $row->setAttribute('student_total_nominal', $studentBills->sum(fn (Bill $bill): float => (float) $bill->nominal));
                $row->setAttribute('student_total_paid', $studentBills->sum(fn (Bill $bill): float => (float) $bill->nominal_terbayar));
                $row->setAttribute('student_total_due', $studentBills->sum(fn (Bill $bill): float => $bill->sisa_tagihan));
                $row->setAttribute('student_status', $studentBills->every(fn (Bill $bill): bool => $bill->status === 'lunas')
                    ? 'lunas'
                    : ($studentBills->contains(fn (Bill $bill): bool => (float) $bill->nominal_terbayar > 0) ? 'sebagian' : 'belum_bayar'));

                return $row;
            })
            ->sortByDesc('id')
            ->values();
    }

    private function paginateGroups(Collection $groups): LengthAwarePaginator
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator($groups->forPage($page, $perPage)->values(), $groups->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return $paginator;
    }
}
