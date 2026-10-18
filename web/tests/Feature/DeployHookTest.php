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
}
