<?php
require_once 'autoloader.php';

use services\actions\HandleWebhook;

$formData = getFormData();

function getFormData() {
    $rawPostData = file_get_contents('php://input');
    $formData = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        parse_str($rawPostData, $formData);
    }

    return $formData;
}

if (!empty($formData)) {
    $handleWebhook = new HandleWebhook();
    $handleWebhook->setHookData($formData)->handle();
}



