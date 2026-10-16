<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama',
        'jenjang',
        'logo',
        'alamat',
        'telepon',
        'email',
        'warna_tema',
        'footer_struk',
        'prefix_nomor_struk',
        'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function adminAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class)->whereHas('roles', fn ($query) => $query->where('name', 'admin_sekolah'));
    }

    public function schoolYears(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SchoolYear::class);
    }

    public function classes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function paymentTypes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentType::class);
    }

    public function bills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
