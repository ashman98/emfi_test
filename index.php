<?php
require_once 'autoloader.php';

use services\actions\HandleWebhook;
use services\db\CreateDbTables;
use services\db\DBconnect;

$method = $_SERVER['REQUEST_METHOD'];
$formData = getFormData($method);

function getFormData($method) {
    $rawPostData = file_get_contents('php://input');
    $formData = [];

//    if ($method === 'POST') {
        parse_str($rawPostData, $formData);
//    }

    return $formData;
}


    if ($method === 'POST') {

        $log = json_encode($formData);
        file_put_contents(__DIR__.'/var/logs/jesic.json', $log . PHP_EOL, FILE_APPEND);

        $handleWebhook = new HandleWebhook();
        $formD = $handleWebhook->setHookData($formData)->handle();

//        $sql = '';
//        try {
//            if(!empty($formD)){
//                $db = new DBconnect;
//                $conn = $db->conn();
//
//                $sql = "INSERT INTO ass (name)
//                    VALUES ('".json_encode($formD)."')";
//                // use exec() because no results are returned
//                $conn->exec($sql);
//                echo "New record created successfully";
//            }
//        } catch(PDOException $e) {
//            echo $sql . "<br>" . $e->getMessage();
//        }
}


$d = new CreateDbTables();
//$d->create();

echo "</br> Json </br>";
$file = __DIR__.'/var/logs/jesic.json';

$handle = fopen($file, 'r');

while (!feof($handle)) {
    $line = fgets($handle);
    echo $line . "<br>";
}

fclose($handle);

echo "</br></br></br> Log </br>";

$filed = __DIR__.'/var/logs/log.txt';

$handled = fopen($filed, 'r');

while (!feof($handled)) {
    $line = fgets($handled);
    echo $line . "<br>";
}

fclose($handled);




