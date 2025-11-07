<?php
$conn = pg_connect("host=localhost dbname=chiangmai_gis user=postgres password=postgres");
$res = pg_query($conn, "SELECT DISTINCT type FROM tourist_chiangmai ORDER BY type;");
$data = [];
while($r = pg_fetch_assoc($res)){ $data[] = $r; }
echo json_encode($data, JSON_UNESCAPED_UNICODE);
pg_close($conn);
?>
