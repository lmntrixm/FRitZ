<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeOption extends Model
{
    protected $guarded = ['id'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CategoryAttribute::class, 'attribute_id');
    }
}
