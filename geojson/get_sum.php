<?php
require '../config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

$geojson = file_get_contents('bairros.geojson');
$data = json_decode($geojson, true);

$dti = $_GET['dti'];
$dtf = $_GET['dtf'];

if(isset($_GET['setor']) && $_GET['setor'] != 99){
	$setor = $_GET['setor'];
	$sql = "SELECT e.bairro, COUNT(a.codigo) as total 
			FROM atendimento a 
			INNER JOIN endereco e ON e.codigo = a.fkEndereco 
			WHERE a.fksetor = $setor AND
			a.data_atendimento BETWEEN '$dti' AND '$dtf'			
			GROUP BY e.bairro ORDER BY total DESC";
}

else
$sql = "SELECT e.bairro, COUNT(a.codigo) as total 
		FROM atendimento a 
		INNER JOIN endereco e ON e.codigo = a.fkEndereco 
		WHERE a.data_atendimento BETWEEN '$dti' AND '$dtf'
		GROUP BY e.bairro ORDER BY total DESC";
	
$result = mysqli_query($conn, $sql);

$totals = [];
while ($row = mysqli_fetch_assoc($result)) $totals[$row['bairro']] = $row['total'];

$features = array();
foreach ($data['features'] as &$feature) {
  $bairro = $feature['properties']['name'];
  
  if (isset($totals[$bairro])){
	$feature['properties']['sum'] = $totals[$bairro];
	$features[] = $feature;
  }
  else $feature['properties']['sum'] = 0;
}

header('Content-Type: application/json');
echo json_encode($features);
?>
