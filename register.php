<?php
require 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$data = array();

	//Validação
	if (empty($_POST['nome']) || !preg_match("/^[a-zA-ZÀ-ÿ0-9 ]*$/u", $_POST["nome"])) 
		$nomeError = "Há caracteres inválidos no nome.";
	else $data['nome'] = test_input($_POST["nome"]);

	if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) 
		$emailError = "Endereço de e-mail inválido.";
	else $data['email'] = test_input($_POST["email"]);

	if (!preg_match("/^[a-zA-Z0-9]*$/",$_POST['login'])) 
		$loginError = "Login inválido. Somente caracteres alfanuméricos são permitidos.";
	else $data['login'] = test_input($_POST["login"]);

	if (empty($_POST['senha'])) {
		$senhaError = "Senha inválida. A senha não pode estar em branco.";
	} else {
		$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
		$data['senha'] = $senha;
	}
	
	if (empty($_POST['setor']) || $_POST['setor'] == "Escolha um setor") 
		$setorError = "Selecione uma opção.";
	else $data['setor'] = test_input($_POST["setor"]);

	//Endpoint
	if(!empty($data['email']) && !empty($data['login']) && !empty($data['senha']) && !empty($data['setor'])){

		$endpoint_url = "http://" . $_SERVER['SERVER_NAME'] . "/api/tecnicos";

		$post_data = json_encode(array(
			'nome' => $data['nome'],
			'email' => $data['email'],
			'login' => $data['login'],
			'senha' => $senha,
			'setor' => $data['setor']
		));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code === 201) {
			$data['success'] = true;
			setcookie('auth', $data['login'].':'.$senha.':'.$data['setor'], time()+3600*24*30);
			ob_start();
			header("Location: login.php");
			ob_end_flush();
		}
		else if ($http_code === 409) {
			$data['success'] = false;
			$loginError = 'Já existe um usuário com o mesmo login.';
		}
		else {
			$data['success'] = false;
			$data['error'] = 'Usuário ou senha inválidos.';
		}
		
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
		
			<?php if(isset($nomeError)): ?>
            <label class="form-label" for="nome">Nome:</label>
            <input class="form-control is-invalid" type="text" id="nome" name="nome" value="<?= $_POST['nome'] ?>" required>
			<span class="invalid-feedback"><?= $nomeError ?></span>
			<?php else: ?>
			<label class="form-label" for="nome">Nome:</label>
            <input class="form-control" type="text" id="nome" name="nome" value="<?= @$_POST['nome'] ?>" required>
			<?php endif; ?>

			<?php if(isset($emailError)): ?>
			<label class="form-label" for="email">Email:</label>
			<input class="form-control is-invalid" type="email" id="email" name="email" value="<?= $_POST['email'] ?>" required>
			<span class="invalid-feedback"><?= $emailError ?></span>
			<?php else: ?>
            <label class="form-label" for="email">Email:</label>
			<input class="form-control" type="email" id="email" name="email" value="<?= @$_POST['email'] ?>" required>
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
			
			<?php
			//Popula o select com os setores
			$sql = "SELECT codigo, nome FROM setor where codigo != 99";
			$result = mysqli_query($conn, $sql);
			?>
			
			<?php if(isset($setorError)): ?>
			<label class="form-label" for="setor">Setor:</label>
			<select class="form-select is-invalid" name="setor">
				<option selected>Escolha um setor</option>
				<?php while($row = mysqli_fetch_assoc($result)): ?>
				<option value="<?= $row['codigo'] ?>"><?= $row['nome'] ?></option>
				<?php endWhile; ?>
			</select>
			<div class="invalid-feedback"><?= $setorError ?></div>
			<?php else: ?>
			<label class="form-label" for="setor">Setor:</label>
			<select class="form-select" name="setor">
				<option selected>Escolha um setor</option>
				<?php while($row = mysqli_fetch_assoc($result)): ?>
				<option value="<?= $row['codigo'] ?>"><?= $row['nome'] ?></option>
				<?php endWhile; ?>
			</select>
			<?php endif; ?>
			
            <button class="mt-3 col-12 btn btn-primary" type="submit">Registrar</button>
			
			<?php mysqli_close($conn); ?>
        </form>
    </body>
</html>
