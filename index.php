<?php
require_once 'autoloader.php';

use services\actions\HandleWebhook;
use services\db\CreateDbTables;
use services\db\DBconnect;

$formData = getFormData();

$fimid = $formData;
function getFormData() {
    $rawPostData = file_get_contents('php://input');
    $formData = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        parse_str($rawPostData, $formData);
    }

    return $formData;
}


    if (!empty($formData)) {

//        $log = json_encode($formData);
//        file_put_contents(__DIR__.'/var/logs/jesic.json', $log . PHP_EOL, FILE_APPEND);
        $handleWebhook = new HandleWebhook();
        $formD = $handleWebhook->setHookData($formData)->handle();
        $fimid = $formD;
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




