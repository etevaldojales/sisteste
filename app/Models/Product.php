<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'category',
        'status',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the full URL for the product's image.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return Storage::disk('public')->url($value);
        }

        return null;
    }

    /**
     * Resolve the route binding for the given ID.
     * Include soft-deleted products in the lookup.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
                    ->withTrashed()
                    ->firstOrFail();
    }
}
