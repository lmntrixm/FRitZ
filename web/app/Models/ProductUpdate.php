<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One file update (A-Update): its analysis, options, result and backup. Table «updates». */
class ProductUpdate extends Model
{
    protected $table = 'updates';

    protected $guarded = ['id'];

    protected $casts = ['diff' => 'array', 'applied_at' => 'datetime', 'rolled_back_at' => 'datetime'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Only the most recent applied update can be rolled back: later ones were built on top of it. */
    public function canRollback(): bool
    {
        return $this->status === 'applied' && $this->backup_path
            && ! static::query()->where('status', 'applied')->where('id', '>', $this->id)->exists();
    }
}
