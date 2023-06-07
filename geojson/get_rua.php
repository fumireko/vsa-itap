<?php
if(!isset($_GET['rua'])) die("Parâmetro 'rua' não definido");

$geojson = json_decode(file_get_contents('data.geojson'), true);
$arr = array();

foreach ($geojson['features'] as $feature) {
    $lg = $feature['properties']['id_eixo_novo_numero'];
    if($lg === $_GET['rua']) array_push($arr, $feature);
}

$feature_collection = array(
	"type" => "FeatureCollection",
	"features" => $arr
);

header('Content-Type: application/json');
echo json_encode($feature_collection);
?>
