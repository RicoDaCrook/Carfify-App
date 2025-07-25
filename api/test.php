<?php
header("Content-Type: application/json");
echo json_encode(["status" => "success", "message" => "API läuft perfekt!"], JSON_UNESCAPED_UNICODE);
