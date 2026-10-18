<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Protocol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'category' => ['/c/touch-panels'],
            'filtered' => ['/c/touch-panels?b[]=orvibo&f[os][]=linux'],
            'empty filter' => ['/c/touch-panels?b[]=zennio&f[os][]=android'],
            'product' => ['/p/mixpad-7m'],
            'search' => ['/search'],
            'search results' => ['/search?q=zvi'],
            'compare' => ['/compare?ids=1,2,3'],
            'finder' => ['/finder'],
            'finder results' => ['/finder/results?n[]=1&n[]=6&p[]=zigbee'],
            'about' => ['/about'],
        ];
    }

    /** @dataProvider publicPages */
    public function test_public_page_renders(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('lang="fa"', false);
    }

    /** Client rule: no price, no stock, no cart anywhere public. */
    public function test_public_pages_never_show_price_or_stock(): void
    {
        Product::query()->update(['internal_price' => 987654321]);

        foreach (self::publicPages() as [$url]) {
            $html = $this->get($url)->getContent();
            $this->assertStringNotContainsString('987654321', $html, $url);
            $this->assertStringNotContainsString('internal_price', $html, $url);
            foreach (['موجودی', 'سبد خرید', 'تومان', 'ریال'] as $word) {
                $this->assertStringNotContainsString($word, $html, "{$url} shows «{$word}»");
            }
        }
        $this->getJson('/search/suggest?q=MIX')->assertOk()->assertDontSee('987654321');
    }

    /** IP and Wi-Fi exist only as inactive protocols and never reach the public catalog. */
    public function test_inactive_protocols_are_hidden(): void
    {
        $p = Product::where('model_code', 'MIXPAD-7M')->first();
        $p->protocols()->attach(Protocol::where('code', 'wifi')->value('id'));

        $this->get('/p/mixpad-7m')->assertOk()->assertDontSee('Wi-Fi');
        $this->get('/c/touch-panels')->assertOk()->assertDontSee('Wi-Fi');
        $this->get('/finder')->assertOk()->assertDontSee('Wi-Fi');
    }

    public function test_draft_products_are_not_public(): void
    {
        Product::where('model_code', 'R20A')->update(['status' => 'draft']);

        $this->get('/p/r20a')->assertNotFound();
        $this->get('/c/intercoms')->assertDontSee('R20A');
        $this->get('/search?q=R20A')->assertDontSee('/p/r20a');
    }

    public function test_category_filters_combine_or_within_and_across_groups(): void
    {
        $this->get('/c/touch-panels?b[]=orvibo&b[]=zennio')
            ->assertSee('MIXPAD-7M')->assertSee('ZVIZ50A')->assertDontSee('HYP-E7');

        $this->get('/c/touch-panels?b[]=orvibo&f[display_size][]=4')
            ->assertSee('MIXPAD-M4')->assertDontSee('MIXPAD-7M');

        // empty combination offers «بدون …» with the count each removal gives
        $this->get('/c/touch-panels?b[]=zennio&f[os][]=android')
            ->assertSee('محصولی با این ترکیب پیدا نشد')->assertSee('بدون «');
    }

    public function test_search_matches_model_code_without_separators(): void
    {
        $this->get('/search?q='.urlencode('zviz 50a'))->assertSee('ZVIZ50A');
        $this->get('/search?q='.urlencode('sas24'))->assertSee('SA/S 24.16');
        // Arabic yeh/kaf typed by the visitor still match Persian names
        $this->get('/search?q='.urlencode('كليد'))->assertSee('OT-SW3');
    }
}
