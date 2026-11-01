<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    /** @use HasFactory<\Database\Factories\ProductPriceFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'currency_id',
        'price',
    ];

    protected $table = 'products_prices';

    // Relación hacia el producto
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // Relación hacia la moneda de este precio específico
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
}
