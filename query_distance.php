<?php
// ===============================
// 🌍 query_distance.php (Final Version)
// ค้นหานิสิตภายในรัศมีระยะทางจากพิกัดที่กำหนด
// ===============================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=UTF-8");

// 🛠️ อนุญาตให้เว็บ front-end (JS) เรียกได้แม้อยู่คนละพอร์ต
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ===============================
// ⚙️ DATABASE CONNECTION
// ===============================
$hostname = "localhost";
$database = "agi_std";
$username = "postgres";
$password = "postgres";
$port     = "5432";

$conn = pg_connect("host=$hostname port=$port dbname=$database user=$username password=$password");
if (!$conn) {
  http_response_code(500);
  die(json_encode(["error" => "❌ Database connection failed"]));
}

// ===============================
// 📍 READ PARAMETERS
// ===============================
$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);
$distance = floatval($_GET['distance'] ?? 0);

// ตรวจสอบค่าที่รับมา
if ($lat == 0 || $lng == 0 || $distance <= 0) {
  echo json_encode(["type" => "FeatureCollection", "features" => []]);
  pg_close($conn);
  exit;
}

// ===============================
// 🧭 QUERY FROM ALL TABLES
// ===============================
$tables = ["agi_64", "agi_65", "agi_66", "agi_67"];
$unionParts = [];

foreach ($tables as $t) {
  $unionParts[] = "
    SELECT 
      '$t' AS source, 
      s_id, s_name, faculty, department, curriculum,
      school_name, tambon, amphoe, province,
      ST_AsGeoJSON(geom) AS geojson
    FROM $t
    WHERE geom IS NOT NULL
      AND ST_DWithin(
            geom::geography,
            ST_SetSRID(ST_MakePoint($lng, $lat), 4326)::geography,
            $distance
          )
  ";
}

// รวมทุก batch
$sql = implode(" UNION ALL ", $unionParts);
$res = pg_query($conn, $sql);
if (!$res) {
  error_log(pg_last_error($conn));
  echo json_encode(["error" => "❌ Query failed"]);
  pg_close($conn);
  exit;
}

// ===============================
// 🧱 BUILD GEOJSON
// ===============================
$features = [];
while ($r = pg_fetch_assoc($res)) {
  $geo = json_decode($r["geojson"], true);
  $features[] = [
    "type" => "Feature",
    "geometry" => $geo,
    "properties" => [
      "batch" => str_replace("agi_", "", $r["source"]),
      "s_id" => $r["s_id"],
      "s_name" => $r["s_name"],
      "faculty" => $r["faculty"],
      "department" => $r["department"],
      "curriculum" => $r["curriculum"],
      "school" => $r["school_name"],
      "tambon" => $r["tambon"],
      "amphoe" => $r["amphoe"],
      "province" => $r["province"]
    ]
  ];
}

// ===============================
// 📤 OUTPUT
// ===============================
echo json_encode(["type" => "FeatureCollection", "features" => $features], JSON_UNESCAPED_UNICODE);
pg_close($conn);
?>
