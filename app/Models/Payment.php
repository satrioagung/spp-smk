<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['institution_id', 'bill_id', 'student_id', 'nomor_transaksi', 'tanggal_bayar', 'metode_bayar', 'nominal_bayar', 'petugas_id', 'bukti_file', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal_bayar' => 'date', 'nominal_bayar' => 'decimal:2'];
    }

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function bill(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function bills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Bill::class, 'payment_bill')->withPivot('nominal_bayar')->withTimestamps();
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function petugas(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function getMetodeBayarLabelAttribute(): string
    {
        return ['tunai' => 'Tunai', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'lainnya' => 'Lainnya'][$this->metode_bayar] ?? ucfirst((string) $this->metode_bayar);
    }

    public function getPaymentTypesLabelAttribute(): string
    {
        $types = $this->relationLoaded('bills')
            ? $this->bills->pluck('paymentType.nama')->filter()->unique()->values()
            : collect([$this->bill?->paymentType?->nama])->filter()->unique()->values();

        return $types->join(', ') ?: '-';
    }
}
