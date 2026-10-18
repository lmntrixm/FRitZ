<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Protocol;
use Illuminate\Database\Seeder;

/**
 * SAMPLE products copied from the design artboards, for the test domain only.
 * Model codes other than ZVIZ50A and R20A are samples (see CLAUDE.md).
 * Never run on the live catalog: real data arrives through the admin or a .fritz/Excel update.
 */
class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // code, name, brand, category, protocols, values, extra
            ['MIXPAD-7M', 'تاچ پنل ۷ اینچ MixPad Matter', 'orvibo', 'touch-panels', ['zigbee'], ['os' => 'linux', 'display_size' => 7, 'box_size' => '86x86'], [
                'is_featured' => true,
                'description' => 'پنل لمسی ۷ اینچی برای کنترل یکجای روشنایی، پرده، تهویه و سناریوها از روی دیوار. با پشتیبانی Matter و Zigbee، تجهیزات برندهای مختلف را در یک رابط ساده کنار هم می‌آورد و برای نصب در قوطی استاندارد ۸۶ طراحی شده است.',
                'pitch' => 'همه چراغ‌ها و سناریوها از یک صفحه؛ با کلیدهای Orvibo هماهنگ است.',
            ]],
            ['HYP-E7', 'تاچ پنل ۷ اینچ Hypanel Elite', 'akubela', 'touch-panels', ['zigbee'], ['os' => 'linux', 'display_size' => 7, 'box_size' => '120x86'], ['is_featured' => true]],
            ['ZVIZ50A', 'تاچ پنل ۵ اینچ Z50', 'zennio', 'touch-panels', ['knx'], ['os' => 'linux', 'display_size' => 5, 'box_size' => '86x86'], []],
            ['ST-7', 'تاچ پنل ۷ اینچ SmartTouch', 'abb', 'touch-panels', ['knx'], ['os' => 'linux', 'display_size' => 7, 'box_size' => '120x86'], []],
            ['HYP-P10', 'تاچ پنل ۱۰ اینچ Hypanel Pro', 'akubela', 'touch-panels', ['zigbee'], ['os' => 'android', 'display_size' => 10, 'box_size' => 'surface', 'surface_mount' => true], []],
            ['OT-P10', 'تاچ پنل ۱۰ اینچ اندرویدی', 'ountech', 'touch-panels', ['modbus'], ['os' => 'android', 'display_size' => 10, 'box_size' => 'surface', 'surface_mount' => true], []],
            ['MIXPAD-M4', 'تاچ پنل ۴ اینچ MixPad Mini', 'orvibo', 'touch-panels', ['zigbee'], ['os' => 'linux', 'display_size' => 4, 'box_size' => '86x86'], []],
            ['OT-SW3', 'کلید هوشمند ۳ پل', 'ountech', 'smart-switches', ['modbus'], ['gang' => '3', 'color' => 'white', 'built_in_relay' => 'yes'], ['is_featured' => true]],
            ['ORV-SW3W', 'کلید هوشمند ۳ پل سفید', 'orvibo', 'smart-switches', ['zigbee'], ['gang' => '3', 'color' => 'white', 'built_in_relay' => 'yes'], [
                'pitch' => 'جایگزین کلید فعلی در همان قوطی ۸۶؛ هر پل یک مدار روشنایی.',
            ]],
            ['ORV-SW2B', 'کلید هوشمند ۲ پل مشکی', 'orvibo', 'smart-switches', ['zigbee'], ['gang' => '2', 'color' => 'black', 'built_in_relay' => 'yes'], []],
            ['AKB-SW1G', 'کلید هوشمند ۱ پل طلایی', 'akubela', 'smart-switches', ['zigbee'], ['gang' => '1', 'color' => 'gold', 'built_in_relay' => 'no'], []],
            ['SA/S 24.16', 'رله ۲۴ کانال', 'abb', 'relays-actuators', ['knx'], ['channels' => '24', 'channel_current' => 16], ['is_featured' => true]],
            ['ZIO-AC8', 'اکچوایتور ۸ کانال', 'zennio', 'relays-actuators', ['knx'], ['channels' => '8', 'channel_current' => 10], []],
            ['MTN-RT', 'ترموستات اتاقی KNX', 'schneider', 'thermostats', ['knx'], [], []],
            ['PD-360', 'سنسور حضور سقفی ۳۶۰ درجه', 'panasonic', 'sensors', ['knx'], [], []],
            ['MS-GEN2', 'سرور خانه هوشمند Miniserver', 'loxone', 'servers', [], [], ['is_featured' => true]],
            ['R20A', 'پنل آیفون هوشمند R20A', 'akuvox', 'intercoms', [], [], ['is_featured' => true]],
            ['T-106', 'اسپیکر سقفی ۶ اینچ', 'itc', 'audio', [], [], []],
            ['ZR-2CH', 'رله پشت‌کلیدی ۲ کانال', 'orvibo', 'behind-switch-relays', ['zigbee'], ['channels' => '2'], []],
            ['ZR-3CH', 'رله پشت‌کلیدی ۳ کانال', 'orvibo', 'behind-switch-relays', ['zigbee'], ['channels' => '3'], [
                'pitch' => 'کلیدهای فعلی می‌مانند؛ رله پشت آن‌ها نصب می‌شود.',
            ]],
            ['ORV-CM1', 'موتور پرده هوشمند', 'orvibo', 'curtains', ['zigbee'], [], []],
            ['ZDI-2CH', 'دیمر ۲ کانال', 'zennio', 'dimmers-drivers', ['knx'], [], []],
        ];

        $brands = Brand::pluck('id', 'slug');
        $protocols = Protocol::pluck('id', 'code');

        foreach ($rows as $i => [$code, $name, $brand, $cat, $protos, $values, $extra]) {
            $category = Category::where('slug', $cat)->firstOrFail();
            $p = Product::updateOrCreate(['model_code' => $code], array_merge([
                'name' => $name,
                'brand_id' => $brands[$brand],
                'category_id' => $category->id,
                'status' => 'published',
                'published_at' => now(),
                'sort' => $i,
            ], $extra));
            $p->protocols()->sync(collect($protos)->map(fn ($c) => $protocols[$c]));

            $p->values()->delete();
            foreach ($values as $key => $v) {
                $attr = $category->fields()->where('key', $key)->firstOrFail();
                $row = ['attribute_id' => $attr->id];
                match ($attr->type) {
                    'select', 'multi' => $row['option_id'] = $attr->options()->where('value', (string) $v)->value('id'),
                    'number' => $row['num'] = $v,
                    'bool' => $row['bool'] = $v,
                    default => $row['text'] = $v,
                };
                $p->values()->create($row);
            }
            $p->refreshSearchText();
        }

        $id = Product::pluck('id', 'model_code');
        Product::find($id['MIXPAD-7M'])->compatible()->syncWithPivotValues(
            [$id['ZR-2CH'], $id['PD-360'], $id['MTN-RT'], $id['MS-GEN2']], ['type' => 'compatible'],
        );
    }
}
