<?php

require '../config/config.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Endpoint GET /tecnicos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/api/tecnicos') {

    $sql = "SELECT * FROM tecnico";
    $result = mysqli_query($conn, $sql);
	
    $tecnicos = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $tecnicos[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($tecnicos);
}

// Endpoint GET /tecnicos/{codigo}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $codigo = $_GET['id'];

    $sql = "SELECT * FROM tecnico WHERE codigo = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $tecnico = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($tecnico);
}

// Endpoint GET /tecnicos/{codigo}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['login'])) {
    $codigo = $_GET['login'];

    $sql = "SELECT * FROM tecnico WHERE login = $codigo";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        die();
    }

    $tecnico = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($tecnico);
}

// POST /tecnicos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/api/tecnicos') {
  $data = json_decode(file_get_contents('php://input'), true);
  $nome = $data['nome'];
  $email = $data['email'];
  $login = $data['login'];
  $senha = $data['senha'];
  $setor = $data['setor'];

  // Verificar se o login já existe
  $sql = "SELECT * FROM tecnico WHERE login = '$login'";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    http_response_code(409); // Conflito
    exit;
  }

  $sql = "INSERT INTO tecnico (nome, email, login, senha, setor) VALUES ('$nome', '$email', '$login', '$senha', $setor)";
  if ($conn->query($sql) === TRUE) {
    http_response_code(201);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}


// PUT /tecnicos?{id}
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $data = json_decode(file_get_contents('php://input'), true);
  $nome = $data['nome'];
  $email = $data['email'];
  $login = $data['login'];
  $senha = $data['senha'];

  $sql = "UPDATE tecnico SET nome='$nome', email='$email', login='$login', senha='$senha' WHERE codigo=$id";
  if ($conn->query($sql) === TRUE) {
    http_response_code(200);
  } else {
    http_response_code(400);
  }

  $conn->close();
  exit;
}

// DELETE /tecnicos?{id}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  
  $sql = "DELETE FROM tecnico WHERE codigo=$id";
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
