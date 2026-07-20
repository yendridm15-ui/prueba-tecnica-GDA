<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'dni',
        'id_reg',
        'id_com',
        'email',
        'name',
        'last_name',
        'address',
        'date_reg',
        'status',
    ];

    protected $attributes = [
        'status' => 'A',
    ];

    protected function casts(): array
    {
        return [
            'date_reg' => 'datetime',
            'status' => Status::class,
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'id_reg');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'id_com');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }
}
