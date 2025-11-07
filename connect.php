<?php
$host = "host=localhost";
$port = "port=5432";
$dbname = "dbname=chiangmai_gis";   // 👈 ชื่อฐานข้อมูลของคุณ
$user = "user=postgres";
$password = "password=YOUR_PASSWORD"; // 👈 ใส่รหัสจริง

$conn = pg_connect("$host $port $dbname $user $password");
if (!$conn) {
    die(json_encode(["error" => "❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้"]));
}
?>
