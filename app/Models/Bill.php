<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['institution_id', 'school_year_id', 'student_id', 'payment_type_id', 'bulan', 'tahun', 'nominal', 'nominal_terbayar', 'status', 'jatuh_tempo', 'keterangan'];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'nominal_terbayar' => 'decimal:2', 'jatuh_tempo' => 'date', 'bulan' => 'integer', 'tahun' => 'integer'];
    }

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function schoolYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function paymentType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function groupedPayments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_bill')->withPivot('nominal_bayar')->withTimestamps();
    }

    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    public function getSisaTagihanAttribute(): float
    {
        return max(0, (float) $this->nominal - (float) $this->nominal_terbayar);
    }

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'lunas' => 'Lunas',
            'sebagian' => 'Sebagian',
            default => 'Belum Bayar',
        };
    }

    public function updateStatus(): self
    {
        $paid = (float) $this->nominal_terbayar;
        $nominal = (float) $this->nominal;
        $this->status = $paid <= 0 ? 'belum_bayar' : ($paid >= $nominal ? 'lunas' : 'sebagian');
        return $this;
    }
}
