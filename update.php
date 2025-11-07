<?php
$hostname = "localhost";
$database = "chiangmai_gis";
$username = "postgres";
$password = "postgres";
$port     = "5432";

$conn = pg_connect("host=$hostname port=$port dbname=$database user=$username password=$password");
if (!$conn) {
  http_response_code(500);
  die(json_encode(["status"=>"error","message"=>"❌ Database connection failed"]));
}

header("Content-Type: application/json; charset=UTF-8");

// 🧩 รับค่าจากฟอร์ม
$id          = $_POST['id'] ?? 0;
$name        = $_POST['name'] ?? '';
$type        = $_POST['type'] ?? '';
$amphoe      = $_POST['amphoe'] ?? '';
$tambon      = $_POST['tambon'] ?? '';
$province    = $_POST['province'] ?? 'เชียงใหม่';
$description = $_POST['description'] ?? '';
$open_time   = $_POST['open_time'] ?? '';
$close_time  = $_POST['close_time'] ?? '';
$contact     = $_POST['contact'] ?? '';
$map_link    = $_POST['map_link'] ?? '';
$image_url   = $_POST['image_url'] ?? '';
$lat         = $_POST['latitude'] ?? '';
$lng         = $_POST['longitude'] ?? '';

if (!$id) {
  http_response_code(400);
  die(json_encode(["status"=>"error","message"=>"⚠️ Missing ID"]));
}

// ✅ ตรวจสอบว่ามี lat/lon หรือไม่
if ($lat !== '' && $lng !== '') {
  $sql = "
    UPDATE tourist_chiangmai
    SET name=$1,
        type=$2,
        amphoe=$3,
        tambon=$4,
        province=$5,
        description=$6,
        open_time=$7,
        close_time=$8,
        contact=$9,
        map_link=$10,
        image_url=$11,
        latitude=$12,
        longitude=$13,
        geom = ST_SetSRID(ST_MakePoint($13, $12), 4326)
    WHERE id=$14
  ";

  $params = [
    $name, $type, $amphoe, $tambon, $province, $description,
    $open_time, $close_time, $contact, $map_link, $image_url,
    $lat, $lng, $id
  ];

} else {
  // ⚠️ ถ้าไม่มีพิกัดใหม่ ไม่อัปเดต geom
  $sql = "
    UPDATE tourist_chiangmai
    SET name=$1,
        type=$2,
        amphoe=$3,
        tambon=$4,
        province=$5,
        description=$6,
        open_time=$7,
        close_time=$8,
        contact=$9,
        map_link=$10,
        image_url=$11
    WHERE id=$12
  ";

  $params = [
    $name, $type, $amphoe, $tambon, $province, $description,
    $open_time, $close_time, $contact, $map_link, $image_url, $id
  ];
}

$result = pg_query_params($conn, $sql, $params);

if ($result && pg_affected_rows($result) > 0) {
  echo json_encode(["status"=>"success","message"=>"✅ อัปเดตข้อมูลเรียบร้อย"]);
} else {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"❌ ไม่สามารถอัปเดตข้อมูลได้"]);
}

pg_close($conn);
?>
