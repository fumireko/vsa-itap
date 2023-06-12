<?php
//Verificar o cookie
require 'config/config.php';
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

@$login = explode(':', $_COOKIE['auth'])[0];
@$senha = explode(':', $_COOKIE['auth'])[1];
@$fkSetor = explode(':', $_COOKIE['auth'])[2];
				
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
$sql = "SELECT codigo, senha FROM tecnico WHERE login = '$login'";
	
@$bcrypt = mysqli_fetch_assoc(mysqli_query($conn, $sql))['senha'];
@$fkTecnico = mysqli_fetch_assoc(mysqli_query($conn, $sql))['codigo'];
@$fkEndereco = 9999;

if(isset($_POST['limpar'])){ setcookie('auth', '', time()-3600); header("Refresh: 0"); }

//Valida o NIS e seta a mensagem de erro
if(isset($_POST['nis'])){
	if (checkPISPASEP($_POST['nis'])) $nis = test_input($_POST['nis']);
	else $nisError = "Número do NIS inválido.";
}

//Lógica do endpoint
if(!empty($_POST['fkTecnico']) && !empty($_POST['fkSetor']) && !empty($_POST['fkEndereco']) && !empty($_POST['data_atendimento']) && !empty($_POST['descricao']) && !isset($nisError)){ 
		$data = array();
		$data['tecnico'] = test_input($_POST["fkTecnico"]);
		$data['setor'] = test_input($_POST["fkSetor"]);
		$data['endereco'] = test_input($_POST["fkEndereco"]);
		$data['data'] = test_input($_POST["data_atendimento"]);
		$data['desc'] = test_input($_POST["descricao"]);
		$data['nis'] = $nis;

		$endpoint_url = "http://" . $_SERVER['SERVER_NAME'] . "/api/atendimentos";

		echo $post_data = json_encode(array(
			'fkTecnico' => intval($data['tecnico']),
			'fkSetor' => intval($data['setor']),
			'fkEndereco' => intval($data['endereco']),
			'nis' => $data['nis'],
			'data_atendimento' => $data['data'],
			'descricao' => $data['desc']
		));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$response = curl_exec($ch);
		echo $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if ($http_code === 201) {
			$data['success'] = true;
			header("Location: /?s=1");
		} else {
			$data['success'] = false;
			$data['error'] = 'Dados inválidos.';
		}
	}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <base target="_top">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">

    <title></title>

    <link rel="shortcut icon" type="image/x-icon" href="docs/images/favicon.ico" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js?"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=" async="" defer=""></script>
    <script src="https://unpkg.com/leaflet.gridlayer.googlemutant@latest/dist/Leaflet.GoogleMutant.js?"></script>

    <style>
	.input-text {
		padding: .375rem .75rem;
		font-size: 1rem;
		font-weight: 400;
		line-height: 1.5;
		color: var(--bs-body-color);
		background-color: var(--bs-body-bg);
		background-clip: padding-box;
		border: var(--bs-border-width) solid var(--bs-border-color);
		border-radius: var(--bs-border-radius);
		transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
	}
    </style>
</head>

<body>
	
	<?php if(isset($_COOKIE['auth']) && ($senha = $bcrypt || password_verify($senha, $bcrypt))): ?>
	<nav class="navbar navbar-expand-md navbar-dark bg-dark" style="font-size: 12px;">
	  <div class="container-fluid col">
		<p class="text-light mt-3 col-10">Você acessou como <b><?= $login ?></b>. Hoje é <?= utf8_encode(strftime('%A, %d de %B de %Y')); ?>.</p>
		<form class="d-flex text-light col-2" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
			<input type="hidden" name="limpar" value="1">
			<button class="btn btn-sm btn-outline-secondary form-control" type="submit">Sair</button>
		</form>
	  </div>
	</nav>
	
		<div class="container mt-2 justify-content-center">
		  <div class="bg-light p-4 rounded h-100">
			<h1>Registrar Atendimento</h1>
			
			<p class="lead">Verifique a localização atual e o lote marcado em <span class="text-danger">vermelho</span>.</p>
		  </div>
		</div>
		
	<datalist id="atendimentos-setor"></datalist>
	
	<div class="container mt-2">
	  <div class="bg-light p-4 rounded">
		<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
			<div class="input-group">
				<input type="hidden" name="fkTecnico" value="<?= $fkTecnico ?>">
				<input type="hidden" name="fkSetor" value="<?= $fkSetor ?>">
				<input type="hidden" name="fkEndereco" value="<?= $fkEndereco ?>">
				
				<?php if(isset($nisError)): ?>
				<select list="atendimentos-setor" class="input-text col-4" type="text" id="desc" name="descricao" placeholder="Descrição" value="<?= @$_POST['descricao'] ?>" required autocomplete="off"></select>
				<input class="form-control col-4 is-invalid" type="text" name="nis" id="nis" required autocomplete="off">
				<input class="input-text col-4" type="date" id="data_atendimento" name="data_atendimento" required>
				<span class="invalid-feedback"><?= $nisError ?></span>
				<?php else: ?>
				<select list="atendimentos-setor" class="input-text col-4" type="text" id="desc" name="descricao" placeholder="Descrição" value="<?= @$_POST['descricao'] ?>" required autocomplete="off"></select>
				<input class="input-text col-4" type="text" id="nis" name="nis" placeholder="NIS" required autocomplete="off">
				<input class="input-text col-4" type="date" id="data_atendimento" name="data_atendimento" required>
				<?php endif; ?>
				
			</div>
			<div align="center">
				<button class="btn btn-sm btn-primary col-6 mt-2" type="submit" id="botao-sel">Enviar</button>
			</div>
		</form>
	  </div>
	</div>
	
	<?php else: header("Location: login.php"); ?>
	<?php endif; ?>

    <script>
		document.querySelector("#data_atendimento").value = new Date().toISOString().substr(0,10);
		
		//Populando as datalists
		document.querySelector('#desc').addEventListener('focus', function() {
		  const datalistD = document.getElementsByTagName('select')[0]
		  datalistD.innerHTML = '';
		  fetch('api/tipos_atendimento?setor=<?= $fkSetor ?>')
			.then(response => response.json())
			.then(data => {
			console.log(JSON.stringify(data));
			  data.forEach(item => {
				const option = document.createElement('option');
				option.value = item.tipo;
				option.innerHTML = item.tipo;
				datalistD.appendChild(option);
			  });
			});
		});
		
		document.querySelector("#nis").addEventListener('input', e => {
			let formattedInput = e.target.value.replace(/\D/g, '');
			formattedInput = formattedInput.replace(/^(\d{3})(\d{5})(\d{2})(\d{1})$/, '$1.$2.$3-$4');
			e.target.value = formattedInput;
			if (e.target.value.length > 14) e.target.value = e.target.value.slice(0, 14);
		});
    </script>

</body>

</html>
