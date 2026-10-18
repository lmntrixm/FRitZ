<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductUpdate;
use App\Models\User;
use App\Support\FritzPackage;
use App\Support\ProductSheet;
use App\Support\Xlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'سارا', 'email' => 'a@fritz.test', 'password' => 'secret-pass-123', 'role' => 'admin']);
    }

    /** The catalog export with one renamed product and one new row. */
    private function editedSheet(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fzt').'.xlsx';
        (new ProductSheet)->export($path);
        $rows = Xlsx::read($path);
        $header = array_shift($rows);
        $col = array_flip($header);
        $rows = array_values($rows);
        foreach ($rows as &$r) {
            if ($r[$col['کد مدل']] === 'ZVIZ50A') {
                $r[$col['نام']] = 'آیفون تصویری ویرایش‌شده';
                $template = $r;
            }
        }
        unset($r);
        $new = array_fill(0, count($header), '');
        foreach (['برند', 'دسته', 'کشور سازنده'] as $h) {
            $new[$col[$h]] = $template[$col[$h]];
        }
        $new[$col['کد مدل']] = 'NEW-100';
        $new[$col['نام']] = 'محصول تازه از اکسل';
        $new[$col['وضعیت']] = 'پیش‌نویس';
        $rows[] = $new;
        Xlsx::write($path, $header, $rows);

        return $path;
    }

    private function upload(string $kind, string $path, string $name)
    {
        return $this->actingAs($this->admin)->post('/admin/update', ['kind' => $kind, 'file' => new UploadedFile($path, $name, null, null, true)]);
    }

    public function test_xlsx_preview_apply_and_rollback(): void
    {
        $before = Product::count();
        $res = $this->upload('xlsx', $this->editedSheet(), 'products.xlsx');
        $u = ProductUpdate::firstOrFail();
        $res->assertRedirect('/admin/update/'.$u->id);
        $this->assertSame('preview', $u->status);
        $this->assertSame(1, $u->diff['counts']['new']);
        $this->assertSame(1, $u->diff['counts']['mod']);
        $change = collect($u->diff['items'])->firstWhere('code', 'ZVIZ50A')['changes'][0];
        $this->assertSame(['نام', 'آیفون تصویری ویرایش‌شده'], [$change['f'], $change['a']]);

        $this->actingAs($this->admin)->get('/admin/update/'.$u->id)->assertOk()
            ->assertSee('ZVIZ50A')->assertSee('NEW-100')->assertSee('ادامه: گزینه‌ها');
        $this->assertDatabaseMissing('products', ['model_code' => 'NEW-100']); // preview changes nothing

        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/apply', ['delete_absent' => 0])->assertRedirect('/admin/update/'.$u->id);
        $u->refresh();
        $this->assertSame('applied', $u->status);
        $this->assertSame($before + 1, Product::count());
        $this->assertSame('آیفون تصویری ویرایش‌شده', Product::where('model_code', 'ZVIZ50A')->value('name'));
        $this->assertSame('draft', Product::where('model_code', 'NEW-100')->value('status'));
        Storage::disk('local')->assertExists($u->backup_path);
        $this->assertDatabaseHas('activity_log', ['action' => 'update.apply']);
        $this->actingAs($this->admin)->get('/admin/update?tab=history')->assertOk()->assertSee('products.xlsx')->assertSee('موفق');

        // applying twice is refused
        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/apply')->assertStatus(409);

        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/rollback')->assertRedirect('/admin/update/'.$u->id);
        $this->assertSame('rolled_back', $u->fresh()->status);
        $this->assertSame($before, Product::count());
        $this->assertDatabaseMissing('products', ['model_code' => 'NEW-100']);
        $this->assertNotSame('آیفون تصویری ویرایش‌شده', Product::where('model_code', 'ZVIZ50A')->value('name'));
        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/rollback')->assertStatus(409);
    }

    public function test_delete_absent_needs_acknowledgement(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fzt').'.xlsx';
        $p = Product::where('model_code', 'ZVIZ50A')->first();
        Xlsx::write($path, ['کد مدل', 'نام'], [['ZVIZ50A', 'فقط همین']]);
        $this->upload('xlsx', $path, 'one.xlsx');
        $u = ProductUpdate::firstOrFail();
        $absent = $u->diff['counts']['absent'];
        $this->assertSame(Product::count() - 1, $absent);

        $this->actingAs($this->admin)->from('/admin/update/'.$u->id)->post('/admin/update/'.$u->id.'/apply', ['delete_absent' => 1])
            ->assertSessionHasErrors('delete_ack');
        $this->assertSame('preview', $u->fresh()->status);

        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/apply', ['delete_absent' => 1, 'delete_ack' => 1]);
        $this->assertSame(1, Product::count());
        $this->assertSame($absent, $u->fresh()->diff['report']['deleted']);
        $this->assertSame($p->id, Product::first()->id);

        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/rollback');
        $this->assertSame($absent + 1, Product::count());
    }

    public function test_fritz_package_wrong_key_tampered_and_not_a_package(): void
    {
        $sheet = $this->editedSheet();
        // no key yet
        $this->actingAs($this->admin)->get('/admin/update')->assertOk()->assertSee('هنوز رمز بسته تعیین نشده است');

        $this->actingAs($this->admin)->post('/admin/settings/key', ['password' => 'a-strong-pass-1405', 'password_confirmation' => 'a-strong-pass-1405'])
            ->assertRedirect();
        $this->assertSame('a-strong-pass-1405', FritzPackage::password());
        $this->assertDatabaseHas('activity_log', ['action' => 'settings.key']);

        $dir = sys_get_temp_dir();
        file_put_contents($wrong = $dir.'/wrong.fritz', FritzPackage::encrypt((string) file_get_contents($sheet), 'another-password-xx'));
        $this->upload('fritz', $wrong, 'wrong.fritz')->assertSessionHasErrors(['file' => 'این فایل با رمز دیگری ساخته شده است. رمز بسته در «تنظیمات» با رمز سازنده فایل یکی نیست. هیچ تغییری اعمال نشد.']);

        $blob = FritzPackage::encrypt((string) file_get_contents($sheet), 'a-strong-pass-1405');
        $blob[100] = chr(ord($blob[100]) ^ 1);
        file_put_contents($bad = $dir.'/bad.fritz', $blob);
        $this->upload('fritz', $bad, 'bad.fritz')->assertSessionHasErrors('file');
        $this->assertStringContainsString('مخدوش', session('errors')->first('file'));

        $this->upload('fritz', $sheet, 'sheet.fritz')->assertSessionHasErrors('file');
        $this->assertStringContainsString('بستهٔ .fritz نیست', session('errors')->first('file'));
        $this->assertSame(0, ProductUpdate::count());

        file_put_contents($good = $dir.'/good.fritz', FritzPackage::encrypt((string) file_get_contents($sheet), 'a-strong-pass-1405'));
        $this->upload('fritz', $good, 'good.fritz')->assertRedirect();
        $u = ProductUpdate::firstOrFail();
        $this->assertSame('fritz', $u->kind);
        $this->assertSame(1, $u->diff['counts']['new']);
        $this->actingAs($this->admin)->get('/admin/update/'.$u->id)->assertSee('امضای آن معتبر است');
        $this->actingAs($this->admin)->get('/admin/update/'.$u->id.'/file')->assertOk()->assertDownload('good.fritz');
    }

    public function test_key_change_needs_the_current_key(): void
    {
        FritzPackage::setPassword('first-password-1405');
        $this->actingAs($this->admin)->post('/admin/settings/key', ['current' => 'nope', 'password' => 'second-password-1405', 'password_confirmation' => 'second-password-1405'])
            ->assertSessionHasErrorsIn('key', 'current');
        $this->actingAs($this->admin)->post('/admin/settings/key', ['current' => 'first-password-1405', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrorsIn('key', 'password');
        $this->assertSame('first-password-1405', FritzPackage::password());
        $this->actingAs($this->admin)->post('/admin/settings/key', ['current' => 'first-password-1405', 'password' => 'second-password-1405', 'password_confirmation' => 'second-password-1405'])
            ->assertSessionHasNoErrors();
        $this->assertSame('second-password-1405', FritzPackage::password());
        $this->actingAs($this->admin)->get('/admin/settings')->assertOk()->assertSee(FritzPackage::fingerprint('second-password-1405'))->assertSee('تغییر رمز بسته');
    }

    public function test_bad_files_and_discard(): void
    {
        $txt = tempnam(sys_get_temp_dir(), 'fzt');
        file_put_contents($txt, 'code,name');
        $this->upload('xlsx', $txt, 'products.csv')->assertSessionHasErrors('file');
        $this->upload('xlsx', $txt, 'products.xlsx')->assertSessionHasErrors('file'); // not a zip

        $path = tempnam(sys_get_temp_dir(), 'fzt').'.xlsx';
        Xlsx::write($path, ['نام'], [['بدون کد']]);
        $this->upload('xlsx', $path, 'nocode.xlsx');
        $u = ProductUpdate::firstOrFail();
        $this->assertSame('error', $u->diff['messages'][0]['level']);
        $this->actingAs($this->admin)->post('/admin/update/'.$u->id.'/apply')->assertSessionHasErrors('apply');

        $this->actingAs($this->admin)->delete('/admin/update/'.$u->id)->assertRedirect();
        $this->assertSame(0, ProductUpdate::count());
    }

    public function test_exports_and_packer(): void
    {
        $res = $this->actingAs($this->admin)->get('/admin/update/export');
        $res->assertOk()->assertDownload('fritz-products-'.now()->format('Y-m-d').'.xlsx');
        $rows = Xlsx::read($res->baseResponse->getFile()->getPathname());
        $this->assertSame(Product::count() + 1, count($rows));

        $this->actingAs($this->admin)->get('/admin/update/export?empty=1')->assertOk()->assertDownload('fritz-template.xlsx');

        FritzPackage::setPassword('first-password-1405');
        $this->actingAs($this->admin)->get('/admin/update/packer')->assertOk()->assertDownload('fritz-packer.html')
            ->assertSee('PBKDF2', false)->assertSee(FritzPackage::fingerprint('first-password-1405'))->assertDontSee('first-password-1405');
    }

    public function test_editor_cannot_reach_updates(): void
    {
        $editor = User::create(['name' => 'رضا', 'email' => 'e@fritz.test', 'password' => 'secret-pass-123', 'role' => 'editor']);
        foreach (['/admin/update', '/admin/update/export', '/admin/update/packer'] as $url) {
            $this->actingAs($editor)->get($url)->assertForbidden();
        }
        $this->actingAs($editor)->post('/admin/settings/key', ['password' => 'x-strong-pass-1405', 'password_confirmation' => 'x-strong-pass-1405'])->assertForbidden();
        $this->actingAs($editor)->get('/admin')->assertOk()->assertDontSee('/admin/update"', false);
    }
}
