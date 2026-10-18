<?php

namespace App\Support;

use App\Models\Product;

/**
 * Completeness and publish rules for one product (A-Products «تکمیل اطلاعات», A-ProductEdit).
 *
 * Blockers stop publishing: identity fields and the category fields marked «لازم برای انتشار».
 * Photos, datasheet and protocol are warnings, not blockers: the client has no photos yet,
 * and servers, intercoms and audio have no protocol at all.
 */
class ProductAudit
{
    /** @return array{pct:int, blockers:array<int,string>, warnings:array<int,string>, missing:array<int,string>, sections:array<string,string>} */
    public static function check(Product $p): array
    {
        $p->loadMissing(['category.fields', 'values', 'media', 'protocols']);
        $fields = $p->category?->fields ?? collect();
        $filled = fn ($f) => $p->values->where('attribute_id', $f->id)
            ->contains(fn ($v) => $v->option_id !== null || $v->num !== null || $v->bool !== null || filled($v->text));

        $blockers = [];
        $sections = ['base' => 'ok', 'spec' => 'ok', 'desc' => 'ok', 'media' => 'ok', 'rel' => 'ok', 'int' => 'ok'];
        foreach ($fields->where('required_to_publish', true) as $f) {
            if (! $filled($f)) {
                $blockers[] = '«'.$f->label.'» در مشخصات فنی';
                $sections['spec'] = 'er';
            }
        }

        $hasImage = $p->media->where('pivot.role', 'image')->isNotEmpty();
        $hasSheet = $p->media->where('pivot.role', 'datasheet')->isNotEmpty();
        $hasProto = $p->protocols->where('is_active', true)->isNotEmpty();
        $warnings = array_values(array_filter([
            $hasImage ? null : 'بدون عکس؛ در کاتالوگ طرح جای‌نگهدار نشان داده می‌شود',
            $hasSheet ? null : 'بدون دیتاشیت؛ دکمه دیتاشیت پنهان است',
            $hasProto ? null : 'بدون پروتکل فعال؛ در فیلتر پروتکل و راهنما دیده نمی‌شود',
        ]));

        // the same ten checks as the design's completeness bar
        $specOk = $fields->where('type', '!=', 'bool')->every($filled);
        $checks = [
            'نام' => filled($p->name), 'کد مدل' => filled($p->model_code), 'برند' => (bool) $p->brand_id,
            'دسته' => (bool) $p->category_id, 'پروتکل' => $hasProto, 'توضیحات' => filled($p->description),
            'عکس' => $hasImage, 'دیتاشیت' => $hasSheet, 'مشخصات' => $specOk, 'کشور سازنده' => filled($p->country_of_origin),
        ];
        $missing = array_keys(array_filter($checks, fn ($ok) => ! $ok));

        if (! $hasImage || ! $hasSheet) {
            $sections['media'] = 'wn';
        }
        if (! $specOk && $sections['spec'] === 'ok') {
            $sections['spec'] = 'wn';
        }
        if (mb_strlen((string) $p->description) < 150) {
            $sections['desc'] = 'wn';
        }

        return [
            'pct' => (int) round((count($checks) - count($missing)) / count($checks) * 100),
            'blockers' => $blockers, 'warnings' => $warnings, 'missing' => $missing, 'sections' => $sections,
        ];
    }

    public static function color(int $pct): string
    {
        return $pct >= 90 ? 'var(--okT)' : ($pct >= 60 ? 'var(--wn)' : 'var(--err)');
    }
}
