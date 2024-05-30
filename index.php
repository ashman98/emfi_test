<?php
require_once 'autoloader.php';

use services\actions\HandleWebhook;
use services\db\CreateDbTables;
use services\db\DBconnect;

$formData = getFormData();

//$fimid = $formData;
function getFormData() {
    $rawPostData = file_get_contents('php://input');
    $formData = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        parse_str($rawPostData, $formData);
    }

//    print_r( $formData);
    return $formData;
}

$fimid=$formData;
    if (!empty($formData)) {

//        $log = json_encode($formData);
//        file_put_contents(__DIR__.'/var/logs/jesic.json', $log . PHP_EOL, FILE_APPEND);
        $handleWebhook = new HandleWebhook();
        $handleWebhook->setHookData($formData)->handle();
    }

if (!empty($fimid)){
    $sql = '';
    try {
            $db = new DBconnect;
            $conn = $db->conn();

            $sql = "INSERT INTO ass (name)
                VALUES ('".json_encode($fimid)."')";
            $conn->exec($sql);
            echo "New record created successfully";
    } catch(PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }
}



//$d = new CreateDbTables();
//$d->create();




