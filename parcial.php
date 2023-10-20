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

if(isset($_POST['limpar'])){
	setcookie('auth', '', time()-3600); 
	ob_start();
	header("Refresh: 0"); 
	ob_end_flush();
}

//Valida o NIS e seta a mensagem de erro
if(isset($_POST['nis']) && !isset($_POST['sem_nis'])){
	if (checkPISPASEP($_POST['nis'])) $nis = test_input($_POST['nis']);
	else $nisError = "Número do NIS inválido.";
}
if(isset($_POST['sem_nis'])) $nis = '000.00000.00-0';
//Lógica do endpoint
	if(!empty($_POST['fkTecnico']) && !empty($_POST['fkSetor']) && !empty($_POST['fkEndereco']) && !empty($_POST['data_atendimento']) && !empty($_POST['descricao']) && !isset($nisError)){ 
		$data = array();
		$data['tecnico'] = test_input($_POST["fkTecnico"]);
		$data['setor'] = test_input($_POST["fkSetor"]);
		$data['endereco'] = test_input($_POST["fkEndereco"]);
		$data['data'] = test_input($_POST["data_atendimento"]);
		$data['desc'] = test_input($_POST["descricao"]);
		$data['nis'] = $nis;
		$data['nome'] = test_input($_POST['nome']);

		$endpoint_url = "http://" . $_SERVER['SERVER_NAME'] . "/api/atendimentos";

		echo $post_data = json_encode(array(
			'fkTecnico' => intval($data['tecnico']),
			'fkSetor' => intval($data['setor']),
			'fkEndereco' => intval($data['endereco']),
			'nis' => $data['nis'],
			'data_atendimento' => $data['data'],
			'descricao' => $data['desc'],
			'nome' => $data['nome']
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
			ob_start();
			header("Location: /?s=1");
			ob_end_flush();
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
			<p class="lead">Verifique os dados antes de inserir o atendimento.</p>
		  </div>
		</div>
		
	<datalist id="atendimentos-setor"></datalist>
	
	<div class="container mt-2">
	  <div class="bg-light p-4 rounded">
		<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
		
			<input type="hidden" name="fkTecnico" value="<?= $fkTecnico ?>">
			<input type="hidden" name="fkSetor" value="<?= $fkSetor ?>">				
	
			<?php
			$sql = 'select distinct a.codigo, a.bairro from endereco a where geocodigo = 2147483647 order by a.bairro';
			$result = mysqli_query($conn, $sql);
			?>
			<label class="form-check-label" for="checkBairro">Localidade rural:</label>
			<select class="form-select" id="selectBairro" name="fkEndereco">
				<option value="0">Nenhum</option>
				<?php while($row = mysqli_fetch_assoc($result)): ?>
				<option value='<?= $row["codigo"] ?>'><?= $row["bairro"] ?></option>
				<?php endWhile; ?>
			</select>
			
			<?php
			$sql = "SELECT descricao FROM atendimento WHERE fkTecnico = $fkTecnico ORDER BY codigo DESC LIMIT 1;";
			@$ultimoTipo = mysqli_fetch_assoc(mysqli_query($conn, $sql))['descricao'];
			?>
			
			<?php if(isset($nisError)): ?>
			<label for="desc">Tipo de atendimento:</label>
			<select list="atendimentos-setor" class="form-select" id="desc" name="descricao" placeholder="Descrição" value="<?= @$_POST['descricao'] ?>" required autocomplete="off"></select>
			
			<label for="nis">NIS:</label>
			<input class="form-control w-100 is-invalid" type="text" name="nis" id="nis" required autocomplete="off">
			
			<label for="nome">Nome:</label>	
			<input class="input-text w-100" type="text" name="nome" placeholder="Nome completo" required autocomplete="off">
			
			<label for="nis">Data:</label>
			<input class="input-text w-100" type="date" id="data_atendimento" name="data_atendimento" required>
			<span class="invalid-feedback"><?= $nisError ?></span>
			
			<?php else: ?>
			<label for="desc">Tipo de atendimento:</label>
			<select list="atendimentos-setor" class="form-select" id="desc" name="descricao" placeholder="Descrição" value="<?= @$_POST['descricao'] ?>" required autocomplete="off"></select>
			
			<label for="nis">NIS:</label>
			<input class="input-text w-100" type="text" id="nis" name="nis" placeholder="NIS" required autocomplete="off">
			
			<label for="nome">Nome:</label>	
			<input class="input-text w-100" type="text" name="nome" placeholder="Nome completo" required autocomplete="off">
			
			<label for="nis">Data:</label>
			<input class="input-text w-100" type="date" id="data_atendimento" name="data_atendimento" required>
			<?php endif; ?>
				
			<div align="center">
				<input class="form-check-input" type="checkbox" id="sem-nis" name="sem_nis">
				<label class="form-check-label" for="sem-nis">Atendimento sem NIS</label>
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
		document.addEventListener("DOMContentLoaded", function(){
		  const datalistD = document.getElementById('desc');
		  datalistD.innerHTML = '';
		  fetch('api/tipos_atendimento?setor=<?= $fkSetor ?>')
			.then(response => response.json())
			.then(data => {
			  data.forEach(item => {
				if(item.tipo === "<?= $ultimoTipo ?>"){
					const option = document.createElement('option');
					option.value = item.tipo;
					option.innerHTML = item.tipo;
					option.selected = true;
					datalistD.appendChild(option);
				}
				else{
					const option = document.createElement('option');
					option.value = item.tipo;
					option.innerHTML = item.tipo;
					datalistD.appendChild(option);
				}
			  });
		    });
		});
		
		const nisInput = document.querySelector('#nis');
		const semNisInput = document.querySelector('#sem-nis');

		nisInput.addEventListener('input', (e) => {
		  const { value } = nisInput;

		  if (value !== '') {
			semNisInput.checked = false;
			semNisInput.disabled = true;
		  } else {
			semNisInput.disabled = false;
		  }

		  let formattedInput = value.replace(/\D/g, '');
		  formattedInput = formattedInput.replace(/^(\d{3})(\d{5})(\d{2})(\d{1})$/, '$1.$2.$3-$4');
		  nisInput.value = formattedInput.slice(0, 14);
		});

		semNisInput.addEventListener('input', () => {
		  const { checked } = semNisInput;

		  if (checked) {
			nisInput.disabled = true;
			nisInput.value = '';
		  } else {
			nisInput.disabled = false;
		  }
		});
    </script>


</body>

</html>
