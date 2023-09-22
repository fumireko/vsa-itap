<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
//Verificar o cookie
require '../../config/config.php';
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

@$login = explode(':', $_COOKIE['auth'])[0];
@$senha = explode(':', $_COOKIE['auth'])[1];
				
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
$sql = "SELECT senha, ativo FROM tecnico WHERE login = '$login'";
	
@$bcrypt = mysqli_fetch_assoc(mysqli_query($conn, $sql))['senha'];
@$ativo = mysqli_fetch_assoc(mysqli_query($conn, $sql))['ativo'];

if(isset($_POST['limpar']) || $ativo === 0){
	setcookie('auth', '', time()-3600); 
	ob_start();
	header("Refresh: 0"); 
	ob_end_flush();
}

if ($ativo === 0 || !isset($_COOKIE['auth']) || !($senha == $bcrypt || password_verify($senha, $bcrypt))) {
    header("Location: ../../login.php");
    exit; // Ensure that the script stops executing here
}

$sql = "SELECT codigo, nome FROM setor";
$result = $conn->query($sql);
$setores = array();
while ($row = $result->fetch_assoc()) {
    $setores[$row["codigo"]] = $row["nome"];
}

if(isset($_POST['codigo']) && isset($_POST['setor']) && isset($_POST['login'])){
	$login = $_POST['login'];
	$setor = $_POST['setor'];
	$codigo = $_POST['codigo'];
	mysqli_query($conn, "UPDATE tecnico SET login = '$login', senha = '$pass', setor = $setor where codigo = $codigo");
	ob_start();
	header("Location: ../tecnico.php"); 
	ob_end_flush();
}

if(isset($_POST['senha'])){
	$pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
	mysqli_query($conn, "UPDATE tecnico SET senha = '$pass' where codigo = $codigo");
	ob_start();
	header("Location: ../tecnico.php"); 
	ob_end_flush();
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
	<style>
	.arrow {
	  display: inline-block;
	  margin-left: 5px;
	  width: 0;
	  height: 0;
	  border-style: solid;
	  border-width: 5px 4px 0 4px;
	  border-color: #666 transparent transparent transparent;
	}

	.ascending .arrow {
	  border-color: transparent transparent #666 transparent;
	  border-width: 0 4px 5px 4px;
	}

	.descending .arrow {
	  border-color: #666 transparent transparent transparent;
	  border-width: 5px 4px 0 4px;
	}
	</style>
  </head>
  <body>
      
  <?php if(!isset($_COOKIE['auth']) && !($senha = $bcrypt || password_verify($senha, $bcrypt))): header("Location: ../../login.php");  ?>
  <?php else: ?>
  
	<nav class="navbar navbar-expand-md navbar-dark bg-dark" style="font-size: 12px;">
	  <div class="container-fluid col">
		<p class="text-light mt-3 col-10">Você acessou como <b><?= $login ?></b>. Hoje é <?= utf8_encode(strftime('%A, %d de %B de %Y')); ?>.</p>
		<form class="d-flex text-light col-2" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
			<input type="hidden" name="limpar" value="1">
			<button class="btn btn-sm btn-outline-secondary form-control" type="submit">Sair</button>
		</form>
	  </div>
	</nav>
	
	<div class="container mt-3">
	<?php if($login === 'admin'): ?>		
		<div class="container mt-3">
		  <div class="bg-light rounded pb-3">
			<h1 class="text-center pt-3" id="topo">Editar técnico</h1>
			
			<?php
			$codigo = $_GET['i'];
			$sql = "SELECT * FROM tecnico where codigo = $codigo";
			$result = mysqli_query($conn, $sql);
			$setor = mysqli_fetch_assoc($result)["setor"];
			$result = mysqli_query($conn, $sql);
			$login = mysqli_fetch_assoc($result)["login"];
			?>	
			<form class="mx-5 mb-3" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
				<label>Código:</label>
				<input class="form-control" type="number" name="codigo" value="<?= $codigo ?>" readonly>
				<label>Setor:</label>
				<select class="form-select" name="setor">
					<?php foreach ($setores as $codigo => $nome) : ?>
						<?php if($codigo == $setor): ?>
						<option selected value="<?= $codigo ?>"><?= $nome ?></option>
						<?php else: ?>
						<option value="<?= $codigo ?>"><?= $nome ?></option>
						<?php endif ?>
					<?php endforeach; ?>
				</select>
				<label>Login:</label>
				<input class="form-control" type="text" name="login" value="<?= $login ?>">
				<label>Senha:</label>
				<input class="form-control" type="text" name="senha">
				<div class="form-text">Deixe a senha em branco se não deseja alterar a senha.</div>
				
				<div class="input-group">
					<button class="btn btn-sm btn-primary col-6 mt-2" type="submit">Salvar</button>
					<a class="btn btn-sm btn-secondary col-6 mt-2" href="../tecnico.php">Cancelar</a>
				</div>
			</form>
				
			</div>
		</div>	
	<?php endif; ?>
  <?php endif; ?>
  </body>
 </html>