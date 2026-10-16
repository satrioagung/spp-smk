<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $request->session()->get('active_institution_id');

        return view('dashboard', [
            'activeInstitution' => $institutionId ? Institution::find($institutionId) : null,
            'stats' => $this->statistics($institutionId),
        ]);
    }

    public function switchTenant(Request $request, Institution $institution): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403, 'Anda tidak memiliki izin untuk mengganti lembaga.');
        abort_unless($institution->aktif, 422, 'Lembaga yang dipilih sedang tidak aktif.');

        $request->session()->put('active_institution_id', $institution->getKey());

        return to_route('dashboard')->with('success', "Lembaga aktif diganti ke {$institution->nama}.");
    }

    private function statistics(?int $institutionId): array
    {
        if (! $institutionId) {
            return $this->emptyStatistics();
        }

        return [
            'total_siswa' => $this->countByInstitution('students', $institutionId),
            'total_tagihan' => $this->sumByInstitution('bills', 'nominal', $institutionId),
            'total_pembayaran' => $this->sumByInstitution('payments', 'nominal', $institutionId),
            'saldo_kas' => $this->cashBalance($institutionId),
            'total_tunggakan' => $this->sumByInstitution('arrears', 'nominal', $institutionId),
        ];
    }

    private function emptyStatistics(): array
    {
        return ['total_siswa' => 0, 'total_tagihan' => 0, 'total_pembayaran' => 0, 'saldo_kas' => 0, 'total_tunggakan' => 0];
    }

    private function countByInstitution(string $table, int $institutionId): int
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'institution_id')
            ? (int) DB::table($table)->where('institution_id', $institutionId)->count()
            : 0;
    }

    private function sumByInstitution(string $table, string $column, int $institutionId): float|int
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'institution_id') && Schema::hasColumn($table, $column)
            ? (DB::table($table)->where('institution_id', $institutionId)->sum($column) ?: 0)
            : 0;
    }

    private function cashBalance(int $institutionId): float|int
    {
        if (! Schema::hasTable('cash_transactions') || ! Schema::hasColumn('cash_transactions', 'institution_id') || ! Schema::hasColumn('cash_transactions', 'nominal')) {
            return 0;
        }

        $query = DB::table('cash_transactions')->where('institution_id', $institutionId);
        if (! Schema::hasColumn('cash_transactions', 'jenis')) {
            return 0;
        }

        $masuk = (clone $query)->where('jenis', 'masuk')->sum('nominal');
        $keluar = (clone $query)->where('jenis', 'keluar')->sum('nominal');

        return $masuk - $keluar;
    }
}
