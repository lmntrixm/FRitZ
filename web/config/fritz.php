<?php

return [
    // Load the design's sample products. Turn on for the test domain only.
    'demo_data' => (bool) env('FRITZ_DEMO_DATA', false),

    // Shared secret for the post-deploy hook (POST /_deploy with header X-Deploy-Token).
    'deploy_token' => (string) env('DEPLOY_TOKEN', ''),
];
