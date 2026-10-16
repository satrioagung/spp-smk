<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstitutionRequest;
use App\Models\Institution;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Institution::class);

        return view('institutions.index', [
            'institutions' => Institution::with('adminAccount')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Institution::class);

        return view('institutions.create');
    }

    public function store(InstitutionRequest $request): RedirectResponse
    {
        $this->authorize('create', Institution::class);
        $data = $request->validated();
        $accountData = Arr::only($data, ['akun_nama', 'akun_email', 'akun_password']);
        $institutionData = Arr::except($data, array_keys($accountData));

        if ($request->hasFile('logo')) {
            $institutionData['logo'] = $request->file('logo')->store('logos', 'public');
        }

        DB::transaction(function () use ($institutionData, $accountData): void {
            $institution = Institution::create($institutionData);
            $account = $institution->users()->create([
                'name' => $accountData['akun_nama'],
                'email' => $accountData['akun_email'],
                'password' => Hash::make($accountData['akun_password']),
                'aktif' => true,
            ]);
            $account->assignRole('admin_sekolah');
        });

        return to_route('institutions.index')->with('success', 'Lembaga berhasil ditambahkan.');
    }

    public function edit(Institution $institution): View
    {
        $this->authorize('update', $institution);

        return view('institutions.edit', ['institution' => $institution, 'account' => $institution->adminAccount]);
    }

    public function update(InstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $this->authorize('update', $institution);
        $data = $request->validated();
        $accountData = Arr::only($data, ['akun_nama', 'akun_email', 'akun_password']);
        $institutionData = Arr::except($data, array_keys($accountData));

        if ($request->hasFile('logo')) {
            if ($institution->logo) {
                Storage::disk('public')->delete($institution->logo);
            }
            $institutionData['logo'] = $request->file('logo')->store('logos', 'public');
        }

        DB::transaction(function () use ($institution, $institutionData, $accountData): void {
            $institution->update($institutionData);
            $account = $institution->adminAccount()->first();
            if (! $account) {
                $account = $institution->users()->create([
                    'name' => $accountData['akun_nama'],
                    'email' => $accountData['akun_email'],
                    'password' => Hash::make($accountData['akun_password']),
                    'aktif' => true,
                ]);
                $account->assignRole('admin_sekolah');
            } else {
                $account->name = $accountData['akun_nama'];
                $account->email = $accountData['akun_email'];
                if (filled($accountData['akun_password'] ?? null)) {
                    $account->password = Hash::make($accountData['akun_password']);
                }
                $account->save();
            }
        });

        return to_route('institutions.index')->with('success', 'Data lembaga berhasil diperbarui.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $this->authorize('delete', $institution);
        $institution->delete();

        return to_route('institutions.index')->with('success', 'Lembaga berhasil dihapus.');
    }

    public function toggleAktif(Institution $institution): RedirectResponse
    {
        $this->authorize('update', $institution);
        $institution->update(['aktif' => ! $institution->aktif]);
        $status = $institution->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return to_route('institutions.index')->with('success', "Lembaga berhasil {$status}.");
    }
}
