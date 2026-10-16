<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['001', '', 'Budi Santoso', 'L', 'X TKJ 1', '081234567890', 1],
            ['002', '', 'Siti Aminah', 'P', 'X TKJ 1', '081234567891', 1],
        ];
    }

    public function headings(): array
    {
        return ['nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'kelas', 'no_hp_ortu', 'aktif'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
