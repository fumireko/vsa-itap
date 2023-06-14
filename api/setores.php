<?php

require '../config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Endpoint GET /setores
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {

    $sql = "SELECT * FROM setor";
    $result = mysqli_query($conn, $sql);
	
    $setores = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $setores[] = $row;
    }

    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($setores);
}

// Endpoint GET /setores/{codigo}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $codigo = $_GET['id'];

    $sql = "SELECT * FROM setor WHERE codigo = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
         
        die();
    }

    $setores = mysqli_fetch_assoc($result);
    header('Content-Type: application/json'); header('Access-Control-Allow-Origin: *');
    echo json_encode($setores);
}

// POST /setores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/setores') {
  $data = json_decode(file_get_contents('php://input'), true);
  $nome = $data['nome'];

  $sql = "INSERT INTO setor (nome) VALUES ('$nome')";
  if ($conn->query($sql) === TRUE) {
    http_response_code(201);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// PUT /setores?{id}
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $data = json_decode(file_get_contents('php://input'), true);
  $nome = $data['nome'];

  $sql = "UPDATE setor SET nome='$nome' WHERE codigo=$id";
  if ($conn->query($sql) === TRUE) {
    http_response_code(200);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// DELETE /setores?{id}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  
  $sql = "DELETE FROM setor WHERE codigo=$id";
  if ($conn->query($sql) === TRUE) {
    http_response_code(204);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// Endpoint inválido
 
exit;
