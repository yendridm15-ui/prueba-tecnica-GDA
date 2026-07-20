<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\CommuneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commune extends Model
{
    /** @use HasFactory<CommuneFactory> */
    use HasFactory;

    protected $primaryKey = 'id_com';

    public $timestamps = false;

    protected $fillable = [
        'id_reg',
        'description',
        'status',
    ];

    protected $attributes = [
        'status' => 'A',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'id_reg');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'id_com');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }
}
