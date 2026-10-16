<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ModuleThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function seedModule(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_module_seeds_school_years_and_classes(): void
    {
        $this->seedModule();

        $this->assertDatabaseHas('school_years', ['nama' => '2024/2025', 'aktif' => true]);
        $this->assertDatabaseCount('school_years', 4);
        $this->assertDatabaseCount('classes', 7);
    }

    public function test_admin_can_create_and_search_student(): void
    {
        $this->seedModule();
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        $schoolYear = SchoolYear::where('nama', '2024/2025')->where('aktif', true)->firstOrFail();
        $class = SchoolClass::where('school_year_id', $schoolYear->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('students.store'), [
            'nis' => 'B9001',
            'nisn' => '009001',
            'nama_lengkap' => 'Bintang Sembilan',
            'jenis_kelamin' => 'P',
            'class_id' => $class->id,
            'no_hp_ortu' => '08123456789',
            'aktif' => 1,
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', ['nis' => 'B9001', 'nama_lengkap' => 'Bintang Sembilan']);
        $this->actingAs($user)->get(route('students.index', ['search' => 'bintang']))->assertOk()->assertSee('Bintang Sembilan');
    }

    public function test_only_one_school_year_can_be_active(): void
    {
        $this->seedModule();
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        $target = SchoolYear::where('nama', '2025/2026')->whereHas('institution', fn ($query) => $query->where('nama', 'SMK Satria Agung'))->firstOrFail();

        $this->actingAs($user)->post(route('school-years.aktif', $target))->assertRedirect(route('school-years.index'));

        $this->assertDatabaseHas('school_years', ['id' => $target->id, 'aktif' => true]);
        $this->assertSame(1, SchoolYear::where('institution_id', $target->institution_id)->where('aktif', true)->count());
    }

    public function test_student_import_updates_existing_nis(): void
    {
        $this->seedModule();
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        $schoolYear = SchoolYear::where('nama', '2024/2025')->where('aktif', true)->firstOrFail();
        $class = SchoolClass::where('school_year_id', $schoolYear->id)->firstOrFail();
        Student::create(['institution_id' => $user->institution_id, 'school_year_id' => $schoolYear->id, 'class_id' => $class->id, 'nis' => 'B9002', 'nama_lengkap' => 'Nama Lama', 'jenis_kelamin' => 'L', 'aktif' => true]);
        $csv = "nis,nisn,nama_lengkap,jenis_kelamin,kelas,no_hp_ortu,aktif\nB9002,009002,Nama Baru,L,{$class->nama_kelas},0812,1\nB9003,009003,Siswa Baru,P,{$class->nama_kelas},0813,1\n";

        $this->actingAs($user)->post(route('students.import'), ['file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv)])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', ['nis' => 'B9002', 'nama_lengkap' => 'Nama Baru']);
        $this->assertDatabaseHas('students', ['nis' => 'B9003', 'nama_lengkap' => 'Siswa Baru']);
    }

    public function test_student_export_and_template_are_available(): void
    {
        $this->seedModule();
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        Excel::fake();

        $this->actingAs($user)->get(route('students.export', ['format' => 'xlsx']))->assertOk();
        Excel::assertDownloaded('data-siswa.xlsx');
        $this->actingAs($user)->get(route('students.template'))->assertOk();
        Excel::assertDownloaded('template-import-siswa.xlsx');
    }
}
