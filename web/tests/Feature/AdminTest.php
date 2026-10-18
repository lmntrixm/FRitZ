<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $role = 'admin', string $status = 'active'): User
    {
        return User::create(['name' => 'سارا محمدی', 'email' => "$role@fritz.test", 'password' => 'secret-pass-123', 'role' => $role, 'status' => $status]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->user();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }

    public function test_login_and_logout(): void
    {
        $this->user();
        $this->post('/admin/login', ['email' => 'admin@fritz.test', 'password' => 'secret-pass-123'])->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->get('/admin')->assertOk()->assertSee('نمای کلی');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_wrong_password_counts_down_then_locks(): void
    {
        $this->user();
        $this->post('/admin/login', ['email' => 'admin@fritz.test', 'password' => 'nope'])->assertSessionHas('left', 4);
        for ($i = 0; $i < 4; $i++) {
            $this->post('/admin/login', ['email' => 'admin@fritz.test', 'password' => 'nope']);
        }
        // locked: even the right password is refused for 15 minutes
        $this->post('/admin/login', ['email' => 'admin@fritz.test', 'password' => 'secret-pass-123']);
        $this->assertGuest();
    }

    public function test_disabled_users_cannot_sign_in_or_stay_signed_in(): void
    {
        $u = $this->user('admin', 'disabled');
        $this->post('/admin/login', ['email' => 'admin@fritz.test', 'password' => 'secret-pass-123']);
        $this->assertGuest();

        $this->actingAs($u)->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_editor_cannot_open_admin_only_sections(): void
    {
        $editor = $this->user('editor');
        $this->actingAs($editor)->get('/admin')->assertOk()->assertDontSee('href="'.route('admin.settings').'"', false);
        $this->actingAs($editor)->get('/admin/settings')->assertForbidden();
        $this->actingAs($editor)->put('/admin/settings', [])->assertForbidden();
        $this->actingAs($editor)->get('/admin/users')->assertForbidden();
        $this->actingAs($editor)->get('/admin/products')->assertOk();
    }

    public function test_settings_save_and_reach_the_catalog(): void
    {
        $this->actingAs($this->user())->put('/admin/settings', [
            'whatsapp_number' => '۰۹۱۲ ۳۴۵ ۶۷۸۹',
            'whatsapp_name' => 'فروش FRitZ',
            'message_template' => "سلام\n{اقلام}\nنام: {نام}",
            'phone' => '+98 21 1234 5678',
            'email' => 'info@fritz.ir',
            'hours' => 'شنبه تا چهارشنبه',
            'address' => 'تهران',
            'seo_title' => 'FRitZ',
            'seo_description' => 'کاتالوگ',
        ])->assertRedirect('/admin/settings')->assertSessionHas('ok');

        $this->assertSame('989123456789', Setting::get('whatsapp_number'));
        $this->assertFalse(Setting::get('allow_indexing'));
        $this->get('/about')->assertSee('+98 21 1234 5678')->assertSee('info@fritz.ir')->assertSee('wa.me/989123456789', false);
        $this->get('/')->assertSee('"wa":"989123456789"', false)->assertSee('noindex', false);
        $this->assertDatabaseHas('activity_log', ['action' => 'settings.update']);
    }

    public function test_settings_reject_a_template_without_items(): void
    {
        $this->actingAs($this->user())->put('/admin/settings', ['message_template' => 'سلام', 'seo_title' => 'FRitZ'])
            ->assertSessionHasErrors('message_template');
    }

    public function test_first_admin_setup_needs_the_key_and_closes_after(): void
    {
        config(['fritz.deploy_token' => 'setup-key-value']);
        $this->get('/admin/login')->assertRedirect('/admin/setup');
        $this->get('/admin/setup')->assertOk();

        $form = ['name' => 'سارا', 'email' => 'sara@fritz.ir', 'password' => 'long-password-1', 'password_confirmation' => 'long-password-1'];
        $this->post('/admin/setup', $form + ['key' => 'wrong'])->assertSessionHasErrors('key');
        $this->assertDatabaseCount('users', 0);

        $this->post('/admin/setup', $form + ['key' => 'setup-key-value'])->assertRedirect('/admin');
        $this->assertSame('admin', User::sole()->role);

        auth()->logout();
        $this->get('/admin/setup')->assertNotFound();
        $this->post('/admin/setup', $form + ['key' => 'setup-key-value'])->assertNotFound();
    }

    public function test_admin_pages_render_in_persian(): void
    {
        $admin = $this->user();
        foreach (['/admin', '/admin/settings', '/admin/products', '/admin/inquiries'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('lang="fa"', false);
        }
    }
}
