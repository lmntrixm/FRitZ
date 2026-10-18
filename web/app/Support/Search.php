<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/** Search by Persian name or model code; ~400 products, so LIKE over a normalised column is enough. */
class Search
{
    public static function query(string $q): ?Builder
    {
        $norm = Fa::normalize($q);
        if ($norm === '') {
            return null;
        }
        $tokens = array_slice(array_filter(explode(' ', $norm)), 0, 6);
        $compact = Fa::compact($q);

        return Product::catalog()->where(function (Builder $w) use ($tokens, $compact) {
            $w->where(function (Builder $all) use ($tokens) {
                foreach ($tokens as $t) {
                    $all->where('search_text', 'like', '%'.self::escape($t).'%');
                }
            });
            if (mb_strlen($compact) >= 2) {
                $w->orWhere('code_compact', 'like', '%'.self::escape($compact).'%');
            }
        })->orderByRaw('CASE WHEN code_compact LIKE ? THEN 0 ELSE 1 END', [self::escape($compact).'%'])
            ->orderByDesc('is_featured')->orderBy('sort');
    }

    private static function escape(string $s): string
    {
        return addcslashes($s, '%_\\');
    }

    /** Split a model code around the typed text, for the «hl» highlight in suggestions. */
    public static function highlight(string $code, string $q): array
    {
        $q = trim($q);
        $i = $q === '' ? false : mb_stripos($code, $q);

        return $i === false ? ['', '', $code]
            : [mb_substr($code, 0, $i), mb_substr($code, $i, mb_strlen($q)), mb_substr($code, $i + mb_strlen($q))];
    }
}
