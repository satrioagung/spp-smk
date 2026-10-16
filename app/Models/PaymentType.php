<?php

namespace App\Models;

use App\Enums\PaymentTypeKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['institution_id', 'school_year_id', 'nama', 'kode', 'nominal_default', 'tipe', 'bisa_cicil', 'wajib', 'aktif'];

    protected function casts(): array
    {
        return ['nominal_default' => 'decimal:2', 'tipe' => PaymentTypeKind::class, 'bisa_cicil' => 'boolean', 'wajib' => 'boolean', 'aktif' => 'boolean'];
    }

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function schoolYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function bills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function getTipeLabelAttribute(): string
    {
        return $this->tipe instanceof PaymentTypeKind ? $this->tipe->label() : ucfirst((string) $this->tipe);
    }
}
