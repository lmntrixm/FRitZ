<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowcaseProject extends Model
{
    protected $guarded = ['id'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
