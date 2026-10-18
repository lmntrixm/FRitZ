<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeployHookTest extends TestCase
{
    public function test_hook_is_hidden_without_a_configured_token(): void
    {
        config(['fritz.deploy_token' => '']);
        $this->post('/_deploy', [], ['X-Deploy-Token' => ''])->assertNotFound();
    }

    public function test_hook_rejects_a_wrong_token(): void
    {
        config(['fritz.deploy_token' => 'right-token-value']);
        $this->post('/_deploy', [], ['X-Deploy-Token' => 'wrong'])->assertNotFound();
    }

    public function test_browser_form_is_offered_and_the_key_is_checked(): void
    {
        config(['fritz.deploy_token' => 'right-token-value']);
        $this->get('/_deploy')->assertOk()->assertSee('کلید راه‌اندازی');
        $this->post('/_deploy', ['token' => 'wrong'])->assertNotFound();
    }
}
