<?php

require '../config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Endpoint GET /atendimentos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/api/atendimentos') {

    $sql = "SELECT * FROM atendimento";
    $result = mysqli_query($conn, $sql);
	
    $atendimentos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $atendimentos[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{codigo}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $codigo = $_GET['id'];

    $sql = "SELECT * FROM atendimento WHERE codigo = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{endereco}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['endereco'])) {
    $codigo = $_GET['endereco'];

    $sql = "SELECT * FROM atendimento WHERE fkEndereco = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{setor}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['setor'])) {
    $codigo = $_GET['setor'];

    $sql = "SELECT * FROM atendimento WHERE fkSetor = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{tecnico}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['tecnico'])) {
    $codigo = $_GET['tecnico'];

    $sql = "SELECT * FROM atendimento WHERE fkTecnico = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{descricao}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['descricao'])) {
    $codigo = $_GET['descricao'];

    $sql = "SELECT * FROM atendimento WHERE descricao = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{descricao}&{setor}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['descricao']) && isset($_GET['setor'])) {
    $desc = $_GET['descricao'];
	$codigo = $_GET['setor'];

    $sql = "SELECT * FROM atendimento WHERE fkSetor = $codigo and descricao = $desc";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{descricao}&{tecnico}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['descricao']) && isset($_GET['tecnico'])) {
    $desc = $_GET['descricao'];
	$codigo = $_GET['tecnico'];

    $sql = "SELECT * FROM atendimento WHERE fkTecnico = $codigo and descricao = $desc";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{data-inicial}&{data-final}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['data-inicial']) && isset($_GET['data-final'])) {
    $dti = $_GET['data-inicial'];
	$dtf = $_GET['data-final'];

    $sql = "SELECT * FROM atendimento WHERE data_atendimento BETWEEN '$dti' and '$dtf'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{data-inicial}&{data-final}&{endereco}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['data-inicial']) && isset($_GET['data-final'])) {
    $dti = $_GET['data-inicial'];
	$dtf = $_GET['data-final'];
	$codigo = $_GET['endereco'];

    $sql = "SELECT * FROM atendimento WHERE fkEndereco = $codigo AND data_atendimento BETWEEN '$dti' AND '$dtf'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{data-inicial}&{data-final}&{setor}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['data-inicial']) && isset($_GET['data-final'])) {
    $dti = $_GET['data-inicial'];
	$dtf = $_GET['data-final'];
	$codigo = $_GET['setor'];

    $sql = "SELECT * FROM atendimento WHERE fkSetor = $codigo AND data_atendimento BETWEEN '$dti' AND '$dtf'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// Endpoint GET /atendimentos?{data-inicial}&{data-final}&{tecnico}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['data-inicial']) && isset($_GET['data-final'])) {
    $dti = $_GET['data-inicial'];
	$dtf = $_GET['data-final'];
	$codigo = $_GET['tecnico'];

    $sql = "SELECT * FROM atendimento WHERE fkTecnico = $codigo AND data_atendimento BETWEEN '$dti' AND '$dtf'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $atendimentos = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($atendimentos);
}

// POST /atendimentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/api/atendimentos') {
  $data = json_decode(file_get_contents('php://input'), true);
  $data_atendimento = $data['data_atendimento'];
  $nis = $data['nis'];
  $fkEndereco = $data['fkEndereco'];
  $fkSetor = $data['fkSetor'];
  $fkTecnico = $data['fkTecnico'];
  $descricao = $data['descricao'];

  $sql = "INSERT INTO atendimento (data_atendimento, fkEndereco, fkSetor, fkTecnico, descricao, nis) VALUES ('$data_atendimento', $fkEndereco, $fkSetor, $fkTecnico, '$descricao', '$nis')";
  if ($conn->query($sql) === TRUE) {
    http_response_code(201);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// PUT /atendimentos?{id}
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $data = json_decode(file_get_contents('php://input'), true);
  $data_atendimento = $data['data_atendimento'];
  $fkEndereco = $data['fkEndereco'];
  $fkSetor = $data['fkSetor'];
  $fkTecnico = $data['fkTecnico'];
  $descricao = $data['descricao'];

  $sql = "UPDATE atendimento SET data_atendimento='$data_atendimento', fkEndereco=$fkEndereco, fkSetor=$fkSetor, fkTecnico=$fkTecnico, descricao='$descricao' WHERE codigo=$id";
  if ($conn->query($sql) === TRUE) {
    http_response_code(200);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// DELETE /atendimentos?{id}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  
  $sql = "DELETE FROM atendimento WHERE codigo=$id";
  if ($conn->query($sql) === TRUE) {
    http_response_code(204);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// Endpoint inválido
http_response_code(404);
exit;
