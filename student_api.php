<?php
// ===============================
// 🌐 student_api.php (Fixed Curriculum Version)
// ระบบจัดการข้อมูลนิสิต AGI — CRUD + GeoJSON + Dropdown
// ===============================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=UTF-8");

// 🛠️ อนุญาต CORS (ถ้า front-end รันคนละพอร์ต)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
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
// 🧩 Helper Function
// ===============================
function tableOf($batch) {
  return match($batch) {
    '64' => 'agi_64',
    '65' => 'agi_65',
    '66' => 'agi_66',
    '67' => 'agi_67',
    default => null
  };
}

$method = $_SERVER['REQUEST_METHOD'];

// ===============================
// 📍 1. Province List
// ===============================
if (isset($_GET['type']) && $_GET['type'] === 'province_all') {
  $provinces = [
    "กรุงเทพมหานคร","กระบี่","กาญจนบุรี","กาฬสินธุ์","กำแพงเพชร","ขอนแก่น","จันทบุรี","ฉะเชิงเทรา","ชลบุรี","ชัยนาท","ชัยภูมิ",
    "ชุมพร","เชียงราย","เชียงใหม่","ตรัง","ตราด","ตาก","นครนายก","นครปฐม","นครพนม","นครราชสีมา","นครศรีธรรมราช",
    "นครสวรรค์","นราธิวาส","น่าน","บึงกาฬ","บุรีรัมย์","ปทุมธานี","ประจวบคีรีขันธ์","ปราจีนบุรี","ปัตตานี",
    "พระนครศรีอยุธยา","พังงา","พัทลุง","พิจิตร","พิษณุโลก","เพชรบุรี","เพชรบูรณ์","แพร่","ภูเก็ต","มหาสารคาม",
    "มุกดาหาร","แม่ฮ่องสอน","ยโสธร","ยะลา","ร้อยเอ็ด","ระนอง","ระยอง","ราชบุรี","ลพบุรี","ลำปาง","ลำพูน",
    "เลย","ศรีสะเกษ","สกลนคร","สงขลา","สตูล","สมุทรปราการ","สมุทรสงคราม","สมุทรสาคร","สระแก้ว",
    "สระบุรี","สิงห์บุรี","สุโขทัย","สุพรรณบุรี","สุราษฎร์ธานี","สุรินทร์","หนองคาย","หนองบัวลำภู",
    "อำนาจเจริญ","อุดรธานี","อุตรดิตถ์","อุทัยธานี","อุบลราชธานี"
  ];
  $rows = array_map(fn($p)=>["value"=>$p], $provinces);
  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  pg_close($conn);
  exit;
}

// ===============================
// 📍 2. Dropdown Cascade (จังหวัด / อำเภอ / ตำบล)
// ===============================
if (isset($_GET['type']) && in_array($_GET['type'], ['amphoe','tambon'])) {
  $type = $_GET['type'];
  $batch = $_GET['batch'] ?? '';
  $table = tableOf($batch);
  $tables = $table ? [$table] : ["agi_64","agi_65","agi_66","agi_67"];

  $province = $_GET['province'] ?? '';
  $amphoe   = $_GET['amphoe'] ?? '';

  $unionParts = [];
  foreach ($tables as $t) {
    if ($type == 'amphoe' && $province) {
      $province = pg_escape_string($conn, $province);
      $unionParts[] = "SELECT DISTINCT amphoe AS value FROM $t WHERE province ILIKE '%$province%' AND amphoe <> ''";
    } elseif ($type == 'tambon' && $amphoe) {
      $amphoe = pg_escape_string($conn, $amphoe);
      $unionParts[] = "SELECT DISTINCT tambon AS value FROM $t WHERE amphoe ILIKE '%$amphoe%' AND tambon <> ''";
    }
  }

  if (count($unionParts) === 0) { echo json_encode([]); pg_close($conn); exit; }

  $sql = implode(" UNION ", $unionParts) . " ORDER BY value ASC";
  $res = pg_query($conn, $sql);
  $rows = [];
  while ($r = pg_fetch_assoc($res)) $rows[] = $r;
  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  pg_close($conn);
  exit;
}

// ===============================
// 📘 2.5 Dropdown: Curriculum (หลักสูตร)
// ===============================
if (isset($_GET['type']) && $_GET['type'] === 'department_all') {
  // รายชื่อหลักสูตรทั้งหมด 9 หลักสูตร (ตายตัว)
  $curriculums = [
    "เกษตรแม่นยำ",
    "ทรัพยากรธรรมชาติและสิ่งแวดล้อม",
    "เทคโนโลยีชีวภาพทางการเกษตร",
    "ภูมิศาสตร์",
    "วิทยาศาสตร์การเกษตร",
    "วิทยาศาสตร์การประมง",
    "วิทยาศาสตร์และเทคโนโลยีการอาหาร (แผน 1 : สหกิจศึกษา)",
    "วิทยาศาสตร์และเทคโนโลยีการอาหาร (แผน 2 : สหกิจศึกษา)",
    "สัตวศาสตร์และเทคโนโลยีอาหารสัตว์"
  ];

  // ส่งกลับให้ JavaScript ใช้ใน dropdown
  $rows = array_map(fn($v) => ["value" => $v], $curriculums);
  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  pg_close($conn);
  exit;
}



// ===============================
// 📍 3. READ (GET) — ดึงข้อมูลทั้งหมด
// ===============================
if ($method === 'GET') {
  $batch = $_GET['batch'] ?? '';
  $province = $_GET['province'] ?? '';
  $amphoe = $_GET['amphoe'] ?? '';
  $tambon = $_GET['tambon'] ?? '';
  $department = $_GET['department'] ?? '';
  $keyword = $_GET['keyword'] ?? '';

  $where = [];
  if ($province) $where[] = "province ILIKE '%" . pg_escape_string($conn, $province) . "%'";
  if ($amphoe) $where[] = "amphoe ILIKE '%" . pg_escape_string($conn, $amphoe) . "%'";
  if ($tambon) $where[] = "tambon ILIKE '%" . pg_escape_string($conn, $tambon) . "%'";
  if ($department) $where[] = "curriculum ILIKE '%" . pg_escape_string($conn, $department) . "%'";
  if ($keyword) {
    $kw = pg_escape_string($conn, $keyword);
    $where[] = "(s_id ILIKE '%$kw%' OR s_name ILIKE '%$kw%' OR faculty ILIKE '%$kw%' 
                OR department ILIKE '%$kw%' OR curriculum ILIKE '%$kw%' 
                OR school_name ILIKE '%$kw%' OR tambon ILIKE '%$kw%' 
                OR amphoe ILIKE '%$kw%' OR province ILIKE '%$kw%')";
  }
  $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

  if ($batch && in_array($batch, ['64','65','66','67'])) {
      $union_sql = "SELECT '$batch' AS batch, s_id, s_name, faculty, department, curriculum, school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) AS geojson FROM agi_$batch";
  } else {
      $union_sql = "
        SELECT '64' AS batch, s_id, s_name, faculty, department, curriculum, school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) AS geojson FROM agi_64
        UNION ALL
        SELECT '65', s_id, s_name, faculty, department, curriculum, school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) FROM agi_65
        UNION ALL
        SELECT '66', s_id, s_name, faculty, department, curriculum, school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) FROM agi_66
        UNION ALL
        SELECT '67', s_id, s_name, faculty, department, curriculum, school_name, tambon, amphoe, province, ST_AsGeoJSON(geom) FROM agi_67
      ";
  }

  $sql = "SELECT * FROM ($union_sql) all_data $where_sql;";
  $res = pg_query($conn, $sql);

  $fc = ["type" => "FeatureCollection", "features" => []];
  while ($r = pg_fetch_assoc($res)) {
    $geo = json_decode($r["geojson"], true);
    $lon = $geo['coordinates'][0] ?? null;
    $lat = $geo['coordinates'][1] ?? null;

    $fc["features"][] = [
      "type" => "Feature",
      "geometry" => $geo,
      "properties" => [
        "batch" => $r["batch"], 
        "s_id" => $r["s_id"], 
        "s_name" => $r["s_name"],
        "faculty" => $r["faculty"], 
        "department" => $r["department"],
        "curriculum" => $r["curriculum"], 
        "school" => $r["school_name"], 
        "tambon" => $r["tambon"], 
        "amphoe" => $r["amphoe"], 
        "province" => $r["province"],
        "lon" => $lon,
        "lat" => $lat
      ]
    ];
  }

  echo json_encode($fc, JSON_UNESCAPED_UNICODE);
  pg_close($conn);
  exit;
}

// ===============================
// 🗑️ 4. DELETE
// ===============================
if ($method === 'DELETE') {
  parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
  $batch = $q['batch'] ?? '';
  $s_id  = $q['s_id']  ?? '';
  $table = tableOf($batch);

  if (!$table || !$s_id) { 
    http_response_code(400); 
    echo json_encode(["error" => "Missing params"]); 
    pg_close($conn);
    exit;
  }

  $sql = "DELETE FROM $table WHERE s_id = '$s_id';";
  $ok = pg_query($conn, $sql);
  echo json_encode(["ok" => $ok ? true : false]);
  pg_close($conn);
  exit;
}

// ===============================
// 💾 5. CREATE / UPDATE
// ===============================
if ($method === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);
  $batch = $data['batch'];
  $table = tableOf($batch);
  if (!$table) { 
    http_response_code(400); 
    echo json_encode(["error"=>"Invalid batch"]); 
    pg_close($conn);
    exit;
  }

  $id = pg_escape_string($conn, $data['s_id']);
  $name = pg_escape_string($conn, $data['s_name']);
  $faculty = pg_escape_string($conn, $data['faculty']);
  $dept = pg_escape_string($conn, $data['department']);
  $cur = pg_escape_string($conn, $data['curriculum']);
  $school = pg_escape_string($conn, $data['school']); 
  $tambon = pg_escape_string($conn, $data['tambon']);
  $amphoe = pg_escape_string($conn, $data['amphoe']);
  $province = pg_escape_string($conn, $data['province']);
  $lon = floatval($data['lon']);
  $lat = floatval($data['lat']);

  // ตรวจว่าพิกัดต้องไม่เป็น null
  if (!$lon || !$lat) {
    echo json_encode(["ok" => false, "error" => "Missing lat/lon"]);
    pg_close($conn);
    exit;
  }

  // ตรวจว่ามี s_id อยู่แล้วหรือไม่
  $check = pg_query($conn, "SELECT s_id FROM $table WHERE s_id='$id'");

  if (pg_num_rows($check) > 0) {
    // ✅ Update
    $sql = "
      UPDATE $table SET
        s_name = '$name',
        faculty = '$faculty',
        department = '$dept',
        curriculum = '$cur',
        school_name = '$school',
        tambon = '$tambon',
        amphoe = '$amphoe',
        province = '$province',
        longitude = $lon,
        latitude = $lat,
        geom = ST_SetSRID(ST_MakePoint($lon, $lat), 4326)
      WHERE s_id = '$id';
    ";
  } else {
    // ✅ Insert
    $sql = "
      INSERT INTO $table 
        (s_id, s_name, faculty, department, curriculum, school_name, tambon, amphoe, province, longitude, latitude, geom)
      VALUES 
        (
          '$id',
          '$name',
          '$faculty',
          '$dept',
          '$cur',
          '$school',
          '$tambon',
          '$amphoe',
          '$province',
          $lon,
          $lat,
          ST_SetSRID(ST_MakePoint($lon, $lat), 4326)
        );
    ";
  }

  $ok = pg_query($conn, $sql);
  echo json_encode(["ok" => $ok ? true : false]);
  pg_close($conn);
  exit;
}


// ===============================
// ❌ Invalid Request
// ===============================
pg_close($conn);
echo json_encode(["error" => "Invalid request"]);
?>
