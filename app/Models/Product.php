<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'stock',
        'price',
        'is_available',
        'is_favorite',
        'image',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'price' => 'integer',
            'is_available' => 'integer',
            'is_favorite' => 'integer',
        ];
    }

    public function user()
    {
        // One to many relationship (inverse)
        // This product belongs to user
        // This will return the user that owns the product
        return $this->belongsTo(User::class);
    }
}
