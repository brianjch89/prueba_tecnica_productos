<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency_id',
        'tax_cost',
        'manufacturing_cost',
    ];

    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'description' => 'string'
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'currency_product', 'product_id', 'currency_id')
            ->withPivot('id', 'price')
            ->withTimestamps();
    }
}
