<?php

require '../config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Endpoint GET /atendimentos?{setor}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['setor'])) {
    $codigo = $_GET['setor'];
	if($codigo != 99) $sql = "SELECT * FROM tipo_atendimento where setor = $codigo";
	else $sql = "SELECT * FROM tipo_atendimento order by tipo asc;";
    $result = mysqli_query($conn, $sql);
	
    $atendimentos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $atendimentos[] = $row;
    }
	
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($atendimentos);
}

// Endpoint inválido
 
exit;
