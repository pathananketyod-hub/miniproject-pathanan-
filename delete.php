<?php
$hostname = "localhost";
$database = "chiangmai_gis";
$username = "postgres";
$password = "postgres";
$port     = "5432";

// ✅ เชื่อมต่อฐานข้อมูล
$conn = pg_connect("host=$hostname port=$port dbname=$database user=$username password=$password");
if (!$conn) {
  http_response_code(500);
  echo json_encode(["status"=>"error", "message"=>"❌ Database connection failed"]);
  exit;
}

header("Content-Type: application/json; charset=UTF-8");

// ✅ รับค่า id จาก AJAX
$id = $_POST['id'] ?? '';

if (empty($id) || !is_numeric($id)) {
  http_response_code(400);
  echo json_encode(["status"=>"error", "message"=>"⚠️ Missing or invalid ID"]);
  exit;
}

// ✅ ลบข้อมูลตาม id
$sql = "DELETE FROM public.tourist_chiangmai WHERE id = $1";
$result = pg_query_params($conn, $sql, [$id]);

if ($result && pg_affected_rows($result) > 0) {
  echo json_encode([
    "status" => "success",
    "message" => "🗑️ ลบข้อมูลเรียบร้อยแล้ว (ID: $id)"
  ]);
} else {
  http_response_code(404);
  echo json_encode([
    "status" => "error",
    "message" => "❌ ไม่พบข้อมูลหรือไม่สามารถลบได้ (ID: $id)"
  ]);
}

pg_close($conn);
?>
