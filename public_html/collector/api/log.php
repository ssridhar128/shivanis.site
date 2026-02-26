<?php
header("Access-Control-Allow-Origin: https://test.shivanis.site");
header("Content-Type: application/json");

$json = file_get_contents('php://input');
if ($json) {
    file_put_contents('data.txt', $json . PHP_EOL, FILE_APPEND);
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "no data"]);
}
?>