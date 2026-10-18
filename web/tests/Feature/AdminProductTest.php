<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Protocol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'سارا', 'email' => 'a@fritz.test', 'password' => 'secret-pass-123', 'role' => 'admin']);
        // keep test uploads out of the real public/media
        $this->app->usePublicPath(sys_get_temp_dir().'/fritz-public-'.getmypid());
        config(['filesystems.disks.public.root' => public_path('media')]);
        Storage::forgetDisk('public');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(sys_get_temp_dir().'/fritz-public-'.getmypid());
        parent::tearDown();
    }

    private function panelForm(array $over = []): array
    {
        $cat = Category::where('slug', 'touch-panels')->first();
        $box = $cat->fields()->where('key', 'box_size')->first();

        return array_merge([
            'name' => 'تاچ پنل تست', 'model_code' => 'TP-TEST', 'brand_id' => Brand::where('slug', 'zennio')->value('id'),
            'category_id' => $cat->id, 'protocols' => [Protocol::where('code', 'knx')->value('id')],
            'f' => [$box->id => (string) $box->options()->first()->id], 'intent' => 'publish',
        ], $over);
    }

    public function test_list_filters_and_incomplete(): void
    {
        $this->actingAs($this->admin)->get('/admin/products')->assertOk()->assertSee('MIXPAD-7M');
        $this->actingAs($this->admin)->get('/admin/products?q=zviz')->assertSee('ZVIZ50A')->assertDontSee('MIXPAD-7M');
        $this->actingAs($this->admin)->get('/admin/products?incomplete=1&sort=least')->assertOk()->assertSee('٪');
    }

    public function test_create_publish_with_photo_and_datasheet(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', $this->panelForm([
            'images' => [UploadedFile::fake()->image('front.jpg', 2400, 1800)],
            'datasheet' => UploadedFile::fake()->create('sheet.pdf', 300, 'application/pdf'),
            'specs' => [['label' => 'ولتاژ', 'value' => '24 VDC'], ['label' => '', 'value' => '']],
            'internal_price' => '18400000',
        ]))->assertRedirect('/admin/products/tp-test/edit')->assertSessionHas('ok');

        $p = Product::where('model_code', 'TP-TEST')->with('media', 'customSpecs')->first();
        $this->assertSame('published', $p->status);
        $img = $p->images()->first();
        $this->assertSame('image/webp', $img->mime);
        $this->assertSame(1600, $img->width);
        $this->assertFileExists(public_path('media/'.$img->path));
        $this->assertNotNull($p->datasheet());
        $this->assertCount(1, $p->customSpecs);
        $this->assertEquals(18400000, $p->internal_price);

        // public page shows it, never the internal price
        $this->get('/p/tp-test')->assertOk()->assertSee('TP-TEST')->assertDontSee('18400000')->assertDontSee('18,400,000');
    }

    public function test_publish_is_blocked_by_required_category_field(): void
    {
        $form = $this->panelForm();
        $form['f'] = [];
        $this->actingAs($this->admin)->post('/admin/products', $form)->assertSessionHasErrors();
        $this->assertDatabaseMissing('products', ['model_code' => 'TP-TEST']);

        // a draft may be incomplete
        $form['intent'] = 'draft';
        $this->actingAs($this->admin)->post('/admin/products', $form)->assertRedirect();
        $this->assertSame('draft', Product::where('model_code', 'TP-TEST')->value('status'));
        $this->get('/p/tp-test')->assertOk()->assertSee('پیش‌نمایش پیش‌نویس'); // signed-in preview
        auth()->logout();
        $this->get('/p/tp-test')->assertNotFound();
    }

    public function test_model_code_must_be_unique_and_inactive_protocols_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', $this->panelForm(['model_code' => 'ZVIZ50A']))->assertSessionHasErrors('model_code');
        $this->actingAs($this->admin)->post('/admin/products', $this->panelForm(['protocols' => [Protocol::where('code', 'wifi')->value('id')]]))
            ->assertSessionHasErrors('protocols.0');
    }

    public function test_editor_cannot_change_internal_price(): void
    {
        $editor = User::create(['name' => 'رضا', 'email' => 'e@fritz.test', 'password' => 'secret-pass-123', 'role' => 'editor']);
        $p = Product::where('model_code', 'ZVIZ50A')->first();
        $p->update(['internal_price' => 1000]);
        $box = $p->category->fields()->where('key', 'box_size')->first();

        $this->actingAs($editor)->put('/admin/products/'.$p->slug, [
            'name' => $p->name, 'model_code' => $p->model_code, 'brand_id' => $p->brand_id, 'category_id' => $p->category_id,
            'f' => [$box->id => (string) $box->options()->first()->id], 'internal_price' => '5', 'intent' => 'save',
        ])->assertRedirect();
        $this->assertEquals(1000, $p->fresh()->internal_price);
        $this->actingAs($editor)->get('/admin/products/'.$p->slug.'/edit')->assertOk()->assertSee('فقط‌خواندنی');
    }

    public function test_delete_restore_and_bulk_publish(): void
    {
        $p = Product::where('model_code', 'R20A')->first();
        $this->actingAs($this->admin)->delete('/admin/products/r20a')->assertRedirect('/admin/products');
        $this->assertSoftDeleted($p);
        $this->get('/p/r20a')->assertNotFound();

        $this->actingAs($this->admin)->post('/admin/products/bulk', ['action' => 'restore', 'ids' => [$p->id]])->assertSessionHas('ok');
        $this->assertNotSoftDeleted($p);

        $this->actingAs($this->admin)->post('/admin/products/bulk', ['action' => 'draft', 'ids' => [$p->id]]);
        $this->assertSame('draft', $p->fresh()->status);
        $this->actingAs($this->admin)->post('/admin/products/bulk', ['action' => 'publish', 'ids' => [$p->id]]);
        $this->assertSame('published', $p->fresh()->status);
        $this->assertDatabaseHas('activity_log', ['action' => 'product.bulk.publish']);
    }

    public function test_duplicate_makes_a_draft_copy(): void
    {
        $this->actingAs($this->admin)->post('/admin/products/mixpad-7m/duplicate')->assertRedirect();
        $copy = Product::where('model_code', 'MIXPAD-7M-COPY')->first();
        $this->assertSame('draft', $copy->status);
        $this->assertSame(Product::where('model_code', 'MIXPAD-7M')->first()->values()->count(), $copy->values()->count());
    }

    public function test_lookup_returns_matches(): void
    {
        $this->actingAs($this->admin)->getJson('/admin/products/lookup?q=sensor')->assertOk();
        $this->actingAs($this->admin)->getJson('/admin/products/lookup?q=PD-360')->assertJsonFragment(['code' => 'PD-360']);
    }
}
