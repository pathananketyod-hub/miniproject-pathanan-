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

// ✅ รับค่าจากฟอร์ม
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

if(!$name || !$lat || !$lng){
  http_response_code(400);
  die(json_encode(["status"=>"error","message"=>"⚠️ Missing required fields"]));
}

// ✅ เพิ่มข้อมูลลงฐาน
$sql = "
INSERT INTO public.tourist_chiangmai
(name, type, amphoe, tambon, province, description, open_time, close_time, contact, map_link, image_url, latitude, longitude, geom)
VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,ST_SetSRID(ST_MakePoint($13,$12),4326))
";

$params = [$name,$type,$amphoe,$tambon,$province,$description,$open_time,$close_time,$contact,$map_link,$image_url,$lat,$lng];

$result = pg_query_params($conn,$sql,$params);

if($result){
  echo json_encode(["status"=>"success","message"=>"✅ เพิ่มข้อมูลเรียบร้อย"]);
} else {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"❌ เพิ่มข้อมูลไม่สำเร็จ"]);
}

pg_close($conn);
?>
