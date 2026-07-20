<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $primaryKey = 'id_reg';

    public $timestamps = false;

    protected $fillable = [
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

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class, 'id_reg');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }
}
