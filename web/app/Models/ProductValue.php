<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductValue extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['num' => 'float', 'bool' => 'boolean'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CategoryAttribute::class, 'attribute_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'option_id');
    }
}
