<?php

// Estabelece a conexão com o banco de dados
require '../config/config.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Endpoint GET /enderecos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {

    // Executa a query para obter todos os endereços cadastrados no sistema
    $sql = "SELECT * FROM endereco";
    $result = mysqli_query($conn, $sql);

    // Cria um array com os resultados da query
    $enderecos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $enderecos[] = $row;
    }

    // Retorna os endereços como JSON
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($enderecos);
}

// Endpoint GET /enderecos/{codigo}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $codigo = $_GET['id'];

    // Executa a query para obter o endereço correspondente ao código informado
    $sql = "SELECT * FROM endereco WHERE codigo = $codigo";
    $result = mysqli_query($conn, $sql);

    // Verifica se o endereço foi encontrado
    if (mysqli_num_rows($result) === 0) {
         
        die();
    }

    // Retorna o endereço como JSON
    $endereco = mysqli_fetch_assoc($result);
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($endereco);
}

// Endpoint GET /enderecos/{regional}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['regional'])) {
    $codigo = $_GET['regional'];

    // Executa a query para obter o endereço correspondente ao código informado
    $sql = "SELECT * FROM endereco WHERE regional = '$codigo'";
    $result = mysqli_query($conn, $sql);

    // Verifica se o endereço foi encontrado
    if (mysqli_num_rows($result) === 0) {
         
        die();
    }

    // Retorna o endereço como JSON
	$enderecos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $enderecos[] = $row;
    }
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($enderecos);
}

// Endpoint GET /enderecos/{logradouro}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['logradouro'])) {
    $codigo = $_GET['logradouro'];
	$lg = explode(" ", $codigo);
	
	if(is_numeric(end($lg))){
		$num = end($lg);
		$codigo = preg_replace('/\d+$/', '', $codigo);
		header("Location: ../geojson/view.php?lg=$codigo&n=$num");
	}
	else{
	$sql = "SELECT * FROM endereco WHERE logradouro = '$codigo'";
	
    // Executa a query para obter o endereço correspondente ao código informado    
    $result = mysqli_query($conn, $sql);

    // Verifica se o endereço foi encontrado
    if (mysqli_num_rows($result) === 0) {
        die();
    }

    // Retorna o endereço como JSON
	$enderecos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $enderecos[] = $row;
    }
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($enderecos);
	}
}

// Endpoint GET /enderecos/{bairro}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['bairro'])) {
    $codigo = $_GET['bairro'];

    // Executa a query para obter o endereço correspondente ao código informado
    $sql = "SELECT * FROM endereco WHERE bairro = '$codigo'";
    $result = mysqli_query($conn, $sql);

    // Verifica se o endereço foi encontrado
    if (mysqli_num_rows($result) === 0) {
         
        die();
    }

    // Retorna o endereço como JSON
	$enderecos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $enderecos[] = $row;
    }
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($enderecos);
}
mysqli_close($conn);

?>
