<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
echo json_encode(["status" => "ok", "message" => "CORS Test Successful", "php_version" => phpversion()]);
