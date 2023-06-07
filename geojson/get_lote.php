<?php
$geojson = json_decode(file_get_contents('data.geojson'), true);

$point = [$_GET['lon'], $_GET['lat']]; // The point to check

foreach ($geojson['features'] as $feature) {
    $polygons = $feature['geometry']['coordinates'];
    foreach ($polygons as $polygon) {
        if (pointInPolygon($point, $polygon)) {
			header('Content-Type: application/json');
			echo json_encode($feature);
            break 2; // Exit both loops
        }
    }
}

function pointInPolygon($point, $polygon) {
    $x = $point[0];
    $y = $point[1];
    $inside = false;
    for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
        $xi = $polygon[$i][0];
        $yi = $polygon[$i][1];
        $xj = $polygon[$j][0];
        $yj = $polygon[$j][1];
        $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
        if ($intersect) {
            $inside = !$inside;
        }
    }
    return $inside;
}
?>