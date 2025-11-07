<?php
// --- การเชื่อมฐานข้อมูล ---
$hostname = "localhost";
$database = "agi_std";
$username = "postgres";
$password = "postgres";
$port     = "5432";

$conn = pg_connect("host=$hostname port=$port dbname=$database user=$username password=$password");

if (!$conn) {
  echo json_encode(["error" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"]);
  exit;
}

// --- ดึงข้อมูลจากทุกตาราง และใส่ batch (ปี) ---
$sql = "
  SELECT 
    '64'::text AS batch, s_id, s_name, curriculum, department, faculty,
    school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) AS geojson
  FROM agi_64
  UNION ALL
  SELECT 
    '65'::text AS batch, s_id, s_name, curriculum, department, faculty,
    school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) AS geojson
  FROM agi_65
  UNION ALL
  SELECT 
    '66'::text AS batch, s_id, s_name, curriculum, department, faculty,
    school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) AS geojson
  FROM agi_66
  UNION ALL
  SELECT 
    '67'::text AS batch, s_id, s_name, curriculum, department, faculty,
    school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) AS geojson
  FROM agi_67
";

$result = pg_query($conn, $sql);

$geojson = [
  "type" => "FeatureCollection",
  "features" => []
];

while ($row = pg_fetch_assoc($result)) {
  $geojson["features"][] = [
    "type" => "Feature",
    "geometry" => json_decode($row["geojson"]),
    "properties" => [
      "batch"      => $row["batch"],
      "s_id"       => $row["s_id"],
      "s_name"     => $row["s_name"],
      "faculty"    => $row["faculty"],
      "department" => $row["department"],
      "curriculum" => $row["curriculum"],
      "school"     => $row["school_name"],
      "tambon"     => $row["tambon"],
      "amphoe"     => $row["amphoe"],
      "province"   => $row["province"]
    ]
  ];
}

header("Content-Type: application/json; charset=UTF-8");
echo json_encode($geojson, JSON_UNESCAPED_UNICODE);
