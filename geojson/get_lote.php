<?php
require '../config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

$geojson = json_decode(file_get_contents('data.geojson'), true);

if(isset($_GET['filtro']) && isset($_GET['dti']) && isset($_GET['dtf'])){
    $features = array();
    $descricoes = array();
	
	$dti = $_GET['dti'];
	$dtf = $_GET['dtf'];

	if(!isset($_GET['descricao'])) 
		$sql = "SELECT a.descricao, a.fkEndereco, e.regional, e.logradouro, e.numero_residencia 
		FROM atendimento a INNER JOIN endereco e ON e.codigo = a.fkEndereco
		WHERE a.data_atendimento BETWEEN '$dti' AND '$dtf'";
	else{
		$descricao = $_GET['descricao'];
		$sql = "SELECT a.descricao, a.fkEndereco, e.regional, e.logradouro, e.numero_residencia 
		FROM atendimento a INNER JOIN endereco e ON e.codigo = a.fkEndereco 
		WHERE a.descricao = '$descricao' AND a.data_atendimento BETWEEN '$dti' AND '$dtf'";
	}
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)){
        if (!isset($descricoes[$row['fkEndereco']])) {
            $descricoes[$row['fkEndereco']] = array();
        }
        $descricoes[$row['fkEndereco']][] = $row;
    }

    foreach ($geojson['features'] as &$feature) {
        $fkEndereco = null;
        foreach ($descricoes as $fk => $descArray) {
            if (
                $feature['properties']['regional'] === $descArray[0]['regional'] &&
                $feature['properties']['id_eixo_novo_numero'] === $descArray[0]['logradouro'] &&
                $feature['properties']['novo_numero'] === $descArray[0]['numero_residencia']
            ) {
                $fkEndereco = $fk;
                break;
            }
        }

        if (isset($fkEndereco)) {
            $descricao = '';
            foreach ($descricoes[$fkEndereco] as $desc) {
                if ($descricao !== '') {
                    $descricao .= ', ';
                }
                $descricao .= $desc['descricao'];
            }
            $feature['properties']['descricao'] = $descricao;
            $features[] = $feature;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($features);
}

if(isset($_GET['lon']) && isset($_GET['lat'])){
	$point = [$_GET['lon'], $_GET['lat']];

	foreach ($geojson['features'] as $feature) {
		$polygons = $feature['geometry']['coordinates'];
		foreach ($polygons as $polygon) {
			if (pointInPolygon($point, $polygon)) {
				header('Content-Type: application/json');
				echo json_encode($feature);
				break 2;
			}
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