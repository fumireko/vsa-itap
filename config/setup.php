<?php
require_once 'config.php';
$sql = file_get_contents('setup.sql');
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) die("Erro ao conectar com o banco de dados: " . $conn->connect_error);

if ($conn->multi_query($sql)) {
  echo "Setup concluído.";
} else {
  echo "Erro ao executar comandos SQL: " . $conn->error;
}

$conn->close();
?>
