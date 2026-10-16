<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\ReceiptTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'set.tenant'])
    ->name('dashboard');

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('institutions', InstitutionController::class);
    Route::post('institutions/{institution}/toggle', [InstitutionController::class, 'toggleAktif'])
        ->name('institutions.toggle');
    Route::post('/switch-lembaga/{institution}', [DashboardController::class, 'switchTenant'])
        ->name('switch.tenant');
});

Route::middleware(['auth', 'set.tenant', 'role:super_admin|admin_sekolah|bendahara'])->group(function () {
    Route::get('bills/generate', [BillController::class, 'generateForm'])->name('bills.generate.form');
    Route::post('bills/generate', [BillController::class, 'generate'])->name('bills.generate');
    Route::post('bills/preview', [BillController::class, 'preview'])->name('bills.preview');
    Route::resource('payment-types', PaymentTypeController::class);
    Route::resource('bills', BillController::class)->only(['index', 'show', 'destroy']);
    Route::get('payments/{payment}/struk', [PaymentController::class, 'cetakStruk'])->name('payments.struk');
    Route::resource('payments', PaymentController::class)->except(['edit', 'update']);
    Route::get('receipt-templates/{template}/preview', [ReceiptTemplateController::class, 'preview'])->name('receipt-templates.preview');
    Route::resource('receipt-templates', ReceiptTemplateController::class);

    Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
    Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::get('students/import', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');

    Route::resource('school-years', SchoolYearController::class);
    Route::post('school-years/{schoolYear}/aktif', [SchoolYearController::class, 'setAktif'])->name('school-years.aktif');

    Route::resource('classes', ClassController::class);
    Route::resource('students', StudentController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
