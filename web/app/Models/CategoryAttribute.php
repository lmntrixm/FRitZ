<?php

namespace App\Models;

use App\Support\Fa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryAttribute extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_filter' => 'boolean', 'is_compare' => 'boolean', 'in_finder' => 'boolean',
        'required_to_publish' => 'boolean',
        'num_min' => 'float', 'num_max' => 'float', 'num_step' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id')->orderBy('sort');
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multi'], true);
    }

    /** Human text for one stored value of this attribute. */
    public function format(ProductValue $v): string
    {
        return match ($this->type) {
            'select', 'multi' => (string) $v->option?->label,
            'number' => Fa::num($v->num).($this->unit ? ' '.$this->unit : ''),
            'bool' => $v->bool ? 'بله' : 'خیر',
            default => (string) $v->text,
        };
    }
}
