<?php
// ===============================
// 🔹 ตั้งค่าการเชื่อมต่อฐานข้อมูล
// ===============================
$hostname = "localhost";
$database = "chiangmai_gis";   // ⚠️ แก้ให้ตรงกับชื่อฐานข้อมูลของคุณ
$username = "postgres";
$password = "postgres";        // ⚠️ แก้ให้ตรงกับรหัส pgAdmin
$port     = "5432";

$conn = pg_connect("host=$hostname port=$port dbname=$database user=$username password=$password");
if (!$conn) {
  http_response_code(500);
  die(json_encode(["error" => "Database connection failed"]));
}

// ===============================
// 🔹 ตั้งค่า header และปิด warning
// ===============================
header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);

// ===============================
// 🔹 Query ดึงข้อมูลแปลงเป็น GeoJSON
// ===============================
$sql = "
SELECT jsonb_build_object(
  'type', 'FeatureCollection',
  'features', jsonb_agg(feature)
)
FROM (
  SELECT jsonb_build_object(
    'type', 'Feature',
    'geometry', ST_AsGeoJSON(geom)::jsonb,
    'properties', to_jsonb(row) - 'geom'
  ) AS feature
  FROM (
    SELECT 
     id, 
      name,
      type,
      tambon,
      amphoe,
      description,
      open_time,
      close_time,
      contact,
      -- ✅ ต่อ path เต็มของรูปอัตโนมัติ (ตรงกับโฟลเดอร์ images)
      CASE 
        WHEN position('http' in image_url) = 0 THEN 
          'http://localhost/miniproject/data/images/' || image_url
        ELSE 
          image_url
      END AS image_url,
      map_link,
      geom
    FROM tourist_chiangmai
  ) row
) features;
";

// ===============================
// 🔹 รันคำสั่ง SQL และส่งออกผลลัพธ์
// ===============================
$result = pg_query($conn, $sql);
if (!$result) {
  http_response_code(500);
  die(json_encode(["error" => "Query failed"]));
}

$row = pg_fetch_row($result);
echo $row[0]; // ✅ ส่ง JSON ออกไปให้ JavaScript ใช้
pg_close($conn);
?>
