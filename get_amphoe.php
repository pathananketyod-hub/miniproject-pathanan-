<?php
$conn = pg_connect("host=localhost dbname=chiangmai_gis user=postgres password=postgres");
if (!$conn) die(json_encode(["error"=>"DB connection failed"]));

$sql = "SELECT DISTINCT \"AMPHOE_T\" AS amphoe FROM amphoe_chiangmai ORDER BY amphoe";
$res = pg_query($conn, $sql);

$data = [];
while ($row = pg_fetch_assoc($res)) $data[] = $row;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
pg_close($conn);
?>
