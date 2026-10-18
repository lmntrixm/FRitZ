<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\Need;
use App\Models\Protocol;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Reference data from PRODUCT.md and the design: 14 categories with their
 * filters, 10 brands, protocols (IP and Wi-Fi inactive), wizard needs, settings.
 * Safe to run on production; it creates no products.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['KNX', 'knx', 'سیمی؛ پایدار برای ساختمان در حال ساخت', false, true],
            ['Zigbee', 'zigbee', 'بی‌سیم؛ ساختمان آماده، بدون تخریب', true, true],
            ['Modbus', 'modbus', 'سیمی؛ مقرون‌به‌صرفه', false, true],
            ['IP', 'ip', null, false, false],
            ['Wi-Fi', 'wifi', null, true, false],
        ] as $i => [$name, $code, $desc, $wireless, $active]) {
            Protocol::updateOrCreate(['code' => $code], [
                'name' => $name, 'description' => $desc, 'is_wireless' => $wireless, 'is_active' => $active, 'sort' => $i,
            ]);
        }

        foreach (['Zennio', 'Loxone', 'Schneider', 'Orvibo', 'ABB', 'ITC', 'Ountech', 'Panasonic', 'Akuvox', 'Akubela'] as $i => $b) {
            Brand::updateOrCreate(['slug' => strtolower($b)], ['name' => $b, 'sort' => $i]);
        }

        $control = Category::updateOrCreate(['slug' => 'control'], ['name' => 'کنترل و رابط کاربری', 'sort' => 0]);
        $drive = Category::updateOrCreate(['slug' => 'relay-driver'], ['name' => 'رله و درایور', 'sort' => 1]);

        // slug, name, icon, art, parent
        $cats = [
            ['touch-panels', 'تاچ پنل', 'panel', 'panel7', $control],
            ['smart-switches', 'کلید هوشمند', 'switch', 'switch', $control],
            ['thermostats', 'ترموستات', 'thermo', 'thermo', $control],
            ['relays-actuators', 'رله و اکچوایتور', 'relay', 'relay', $drive],
            ['behind-switch-relays', 'رله پشت‌کلیدی', 'backRelay', 'module', $drive],
            ['sensors', 'سنسور', 'sensor', 'sensor', null],
            ['dimmers-drivers', 'دیمر و درایور', 'dimmer', 'module', $drive],
            ['servers', 'سرور', 'server', 'server', null],
            ['interface-modules', 'ماژول رابط', 'iface', 'module', null],
            ['io-modules', 'ورودی / خروجی', 'io', 'relay', null],
            ['power-supplies', 'منبع تغذیه', 'power', 'relay', null],
            ['curtains', 'پرده', 'curtain', 'module', null],
            ['intercoms', 'آیفون و درب‌بازکن', 'intercom', 'intercom', null],
            ['audio', 'صوت', 'audio', 'speaker', null],
        ];
        $bySlug = [];
        foreach ($cats as $i => [$slug, $name, $icon, $art, $parent]) {
            $bySlug[$slug] = Category::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'icon' => $icon, 'art' => $art, 'parent_id' => $parent?->id, 'sort' => $i + 10,
            ]);
        }

        $this->fields($bySlug['touch-panels'], [
            ['os', 'سیستم‌عامل', 'select', null, ['android' => 'اندروید', 'linux' => 'لینوکس']],
            ['display_size', 'اندازه نمایشگر', 'number', 'اینچ', [], ['num_min' => 4, 'num_max' => 10, 'num_step' => 0.5]],
            ['box_size', 'ابعاد قاب', 'select', null, ['86x86' => '۸۶×۸۶', '120x86' => '۱۲۰×۸۶', 'surface' => 'روکار'], ['required_to_publish' => true]],
            ['surface_mount', 'نصب روکار', 'bool', null, [], ['is_filter' => false]],
        ]);
        $this->fields($bySlug['smart-switches'], [
            ['gang', 'تعداد پل', 'select', null, ['1' => '۱ پل', '2' => '۲ پل', '3' => '۳ پل', '4' => '۴ پل'], ['in_finder' => true]],
            ['color', 'رنگ', 'select', null, ['white' => 'سفید', 'black' => 'مشکی', 'grey' => 'طوسی', 'gold' => 'طلایی'], ['in_finder' => true]],
            ['built_in_relay', 'رله داخلی', 'select', null, ['yes' => 'با رله داخلی', 'no' => 'بدون رله']],
        ]);
        $swatches = ['white' => '#F4F4F0', 'black' => '#1A1B17', 'grey' => '#8C8E86', 'gold' => '#C9A961'];
        foreach ($bySlug['smart-switches']->fields()->where('key', 'color')->first()->options as $o) {
            $o->update(['swatch' => $swatches[$o->value] ?? null]);
        }
        $this->fields($bySlug['relays-actuators'], [
            ['channels', 'تعداد کانال', 'select', null, ['4' => '۴', '8' => '۸', '12' => '۱۲', '24' => '۲۴']],
            ['channel_current', 'جریان هر کانال', 'number', 'آمپر', [], ['is_filter' => false]],
        ]);
        $this->fields($bySlug['behind-switch-relays'], [
            ['channels', 'تعداد کانال', 'select', null, ['1' => '۱', '2' => '۲', '3' => '۳'], ['in_finder' => true]],
        ]);

        foreach ([
            ['روشنایی', 'کلید، دیمر و سناریو', 'bulb', ['smart-switches', 'behind-switch-relays', 'dimmers-drivers', 'relays-actuators']],
            ['پرده', 'پرده برقی و کرکره', 'curtain', ['curtains']],
            ['سرمایش و گرمایش', 'ترموستات و فن‌کویل', 'thermo', ['thermostats']],
            ['آیفون و امنیت', 'درب‌بازکن و دوربین', 'shield', ['intercoms', 'sensors']],
            ['صوت', 'اسپیکر سقفی و پخش', 'music', ['audio']],
            ['کنترل مرکزی', 'همه چیز از یک تاچ پنل', 'panel', ['touch-panels', 'servers']],
        ] as $i => [$label, $hint, $icon, $slugs]) {
            $need = Need::updateOrCreate(['label' => $label], ['hint' => $hint, 'icon' => $icon, 'sort' => $i]);
            $need->categories()->sync(collect($slugs)->map(fn ($s) => $bySlug[$s]->id));
        }

        $defaults = [
            // contact details are bracketed placeholders until the client supplies them
            'whatsapp_number' => '',
            'whatsapp_name' => 'FRitZ',
            'message_template' => "سلام، لطفاً برای اقلام زیر استعلام قیمت بفرستید:\n\n{اقلام}\n\nنام: {نام}\nپروژه: {پروژه}\n\nارسال‌شده از کاتالوگ FRitZ",
            'phone' => '[شماره تماس]',
            'email' => '[ایمیل]',
            'hours' => '[ساعات کاری]',
            'address' => '[نشانی]',
            'seo_title' => 'FRitZ Smart Solutions — کاتالوگ تجهیزات خانه هوشمند',
            'seo_description' => 'محصولات خانه هوشمند از ۱۰ برند در پروتکل‌های KNX، Zigbee و Modbus. مشخصات را ببینید، مقایسه کنید و در واتساپ استعلام بگیرید.',
            'allow_indexing' => false,
            'popular_searches' => ['تاچ پنل لینوکسی', 'رله ۱۲ کانال', 'Miniserver', 'آیفون تصویری', 'سنسور حضور', 'کلید ۳ پل'],
        ];
        foreach ($defaults as $k => $v) {
            if (! Setting::query()->whereKey($k)->exists()) {
                Setting::put($k, $v);
            }
        }
    }

    /** @param array<int, array{0:string,1:string,2:string,3:?string,4:array<string,string>,5?:array}> $defs */
    private function fields(Category $cat, array $defs): void
    {
        foreach ($defs as $i => $d) {
            [$key, $label, $type, $unit, $opts] = $d;
            $attr = CategoryAttribute::updateOrCreate(
                ['category_id' => $cat->id, 'key' => $key],
                array_merge(['label' => $label, 'type' => $type, 'unit' => $unit, 'sort' => $i], $d[5] ?? []),
            );
            $j = 0;
            foreach ($opts as $value => $optLabel) {
                $attr->options()->updateOrCreate(['value' => (string) $value], ['label' => $optLabel, 'sort' => $j++]);
            }
        }
    }
}
