<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'config/config.php';
//Validação

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (!preg_match("/^[a-zA-Z0-9]*$/",$_POST['login'])) 
		$loginError = "Login inválido. Somente caracteres alfanuméricos são permitidos.";
	else $login = test_input($_POST["login"]);

	if (empty($_POST['senha'])) 
		$senhaError = "Senha inválida. A senha não pode estar em branco.";
	else $senha = test_input($_POST["senha"]);

	if(!empty($login) && !empty($senha)){
		$conn = new mysqli($servername, $username, $password, $dbname);
		if ($conn->connect_error) {
		  die("Conexão falhou: " . $conn->connect_error);
		}
		$sql = "SELECT senha, setor FROM tecnico WHERE login = '$login'";
		$result = mysqli_query($conn, $sql);
		$row = mysqli_fetch_assoc($result);
		
		if(password_verify($senha, $row['senha'])){
			setcookie('auth', $login.':'.$row['senha'].':'.$row['setor'], time()+3600*24*30);
			ob_start();
			header("Location: /");
			ob_end_flush();
		} else $senhaError = "Senha incorreta.";
	}
}
?>
<html>
    <head>
        <title></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    </head>
    <body>

        <form class="m-5" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
		
			<?php
				//Verifica o cookie pra avisar se ja tá logado
				@$login = explode(':', $_COOKIE['auth'])[0];
				@$senha = explode(':', $_COOKIE['auth'])[1];
				
				$conn = new mysqli($servername, $username, $password, $dbname);
				if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
				$sql = "SELECT senha FROM tecnico WHERE login = '$login'";
				
				@$bcrypt = mysqli_fetch_assoc(mysqli_query($conn, $sql))['senha'];
			?>
			
			<?php if(isset($_COOKIE['auth']) && ($senha = $bcrypt || password_verify($senha, $bcrypt))): ?>
				<div class="alert alert-secondary">
					Você já está logado. Clique <a href="/">aqui</a> para ir para a página inicial.
				</div>
			<?php endif; ?>
		
			<?php if(isset($loginError)): ?>
			<label class="form-label" for="login">Login:</label>
			<input class="form-control is-invalid" type="text" id="login" name="login" value="<?= $_POST['login'] ?>" required>
			<span class="invalid-feedback"><?= $loginError ?></span>
			<?php else: ?>
			<label class="form-label" for="login">Login:</label>
			<input class="form-control" type="text" id="login" name="login" value="<?= @$_POST['login'] ?>" required>
			<?php endif; ?>

			<?php if(isset($senhaError)): ?>
			<label class="form-label" for="senha">Senha:</label>
			<input class="form-control is-invalid" type="password" id="senha" name="senha" required>
			<span class="invalid-feedback"><?= $senhaError ?></span>
			<?php else: ?>
			<label class="form-label" for="senha">Senha:</label>
			<input class="form-control" type="password" id="senha" name="senha" required>
			<?php endif; ?>
			
            <button class="mt-3 col-12 btn btn-primary" type="submit">Login</button>
			
        </form>
    </body>
</html>
