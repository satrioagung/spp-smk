<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['institution_id', 'nama_template', 'header_text', 'footer_text', 'show_logo', 'show_qr', 'ukuran', 'aktif'];

    protected function casts(): array
    {
        return ['show_logo' => 'boolean', 'show_qr' => 'boolean', 'aktif' => 'boolean'];
    }

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
