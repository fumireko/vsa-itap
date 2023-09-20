<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
//Verificar o cookie
require 'config/config.php';
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

@$login = explode(':', $_COOKIE['auth'])[0];
@$senha = explode(':', $_COOKIE['auth'])[1];
				
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
$sql = "SELECT senha, ativo FROM tecnico WHERE login = '$login'";
	
@$bcrypt = mysqli_fetch_assoc(mysqli_query($conn, $sql))['senha'];
@$ativo = mysqli_fetch_assoc(mysqli_query($conn, $sql))['ativo'];

if(isset($_POST['limpar']) || $ativo == 0){
	setcookie('auth', '', time()-3600); 
	ob_start();
	header("Refresh: 0"); 
	ob_end_flush();
}

if ($ativo == 0 || !isset($_COOKIE['auth']) || !($senha == $bcrypt || password_verify($senha, $bcrypt))) {
    header("Location: login.php");
    exit; // Ensure that the script stops executing here
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
	#progress-bar {
      height: 6px;
    }
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
      
  <?php if(!isset($_COOKIE['auth']) && !($senha = $bcrypt || password_verify($senha, $bcrypt))): header("Location: login.php");  ?>
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
	
	<datalist id="logradouros"></datalist>
	<datalist id="numeros"></datalist>
	
	<?php if(isset($_GET['s'])): ?>
		<div class="alert alert-success mt-3 mx-2">
			<span>Atendimento inserido com sucesso.</span>
		</div>
	<?php endif; ?>
	
	<div class="container mt-3">
	  <div class="bg-light p-5 rounded">
		<h1>Atendimentos</h1>
		<p class="lead">Para iniciar um atendimento, escolha a rua e número ou use a sua localização atual.</p>
		<div class="justify-content-center row">
		<form action="atendimento.php" method="post">
			<div class="input-group">
				<input class="input-text col-8" type="text" list="logradouros" id="input-rua" name="logradouro" placeholder="Logradouro" required autocomplete="off">
				<input class="input-text col-4" type="text" list="numeros" id="input-num" name="numero_predial" placeholder="Número predial" required autocomplete="off">
			</div>
			<div class="progress mt-2" style="height: 6px;">
			  <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
			<div class="input-group">
				<button class="btn btn-sm btn-primary col-6 mt-2" type="submit" id="botao-sel">Selecionar</button>
				<button class="btn btn-sm btn-secondary col-6 mt-2" type="button" onclick="checkLote()" id="botao-loc">Usar localização</button>
			</div>
			<a href="parcial.php" class="link-secondary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover mt-2" style="font-size: 12px"><p style="text-align: center" class="mt-2">Atendimento parcial</p></a>
		</form>
		</div>
	  </div>
	</div>
	
	<?php if($login === 'admin'): ?>
	
	<div class="container mt-3">
	  <div class="bg-light p-5 rounded">
		<h1>Mapas</h1>
		<p class="lead">Use os filtros abaixo para gerar mapas.</p>
		<form action="geojson/mapa.php" method="get">
			<?php
			$sql = "SELECT 
			(SELECT data_atendimento FROM atendimento ORDER BY data_atendimento ASC LIMIT 1) AS min_data,
			(SELECT data_atendimento FROM atendimento ORDER BY data_atendimento DESC LIMIT 1) AS max_data;";
			$row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
			?>
			<div class="mb-3 row">
			  <div class="col-6">
				  <label class="form-check-label" for="inputData">Data inicial:</label>
				  <input class="form-control" type="date" name="dti" id="dtInicial"
				  min="<?= $row['min_data'] ?>" max="<?= $row['max_data'] ?>" value="<?= $row['min_data'] ?>">
			  </div>
			 
			  <div class="col-6">
				  <label class="form-check-label" for="inputData">Data final:</label>
				  <input class="form-control" type="date" name="dtf" id="dtFinal"
				  min="<?= $row['min_data'] ?>" max="<?= $row['max_data'] ?>" value="<?= $row['max_data'] ?>">
			  </div>
			</div>
			<?php
			$sql = 'select distinct a.fksetor, s.nome from atendimento a inner join setor s on (s.codigo = a.fksetor) where s.nome != "Teste"';
			$result = mysqli_query($conn, $sql);
			?>
			<div class="form-check form-switch">
			  <input class="form-check-input" type="checkbox" role="switch" id="checkSetor" checked>
			  <label class="form-check-label" for="checkSetor">Filtrar por setor</label>
				<select class="form-select" id="selectSetor" name="setor">
					<option value="99">Todos</option>
					<?php while($row = mysqli_fetch_assoc($result)): ?>
					<option value='<?= $row["fksetor"] ?>'><?= $row["nome"] ?></option>
					<?php endWhile; ?>
				</select>
			</div>
			<?php
			$sql = 'select distinct a.descricao from atendimento a';
			$result = mysqli_query($conn, $sql);
			?>
			<div class="form-check form-switch">
			  <input class="form-check-input" type="checkbox" role="switch" id="checkAtendimento">
			  <label class="form-check-label" for="checkAtendimento">Filtrar por atendimento</label>
				<select class="form-select"id="selectAtendimento" name="descricao" disabled>
					<option value="filtro">Todos</option>
					<?php while($row = mysqli_fetch_assoc($result)): ?>
					<option value='<?= $row["descricao"] ?>'><?= $row["descricao"] ?></option>
					<?php endWhile; ?>
				</select>
			</div>
			<?php
			$sql = 'SELECT DISTINCT bairro FROM endereco ORDER BY endereco.bairro ASC;';
			$result = mysqli_query($conn, $sql);
			?>
			<div class="form-check form-switch">
			  <input class="form-check-input" type="checkbox" role="switch" id="checkBairro">
			  <label class="form-check-label" for="checkBairro">Filtrar por bairro</label>
				<select class="form-select"id="selectBairro" name="bairro" disabled>
					<option value="filtro">Todos</option>
					<?php while($row = mysqli_fetch_assoc($result)): ?>
					<option value='<?= $row["bairro"] ?>'><?= $row["bairro"] ?></option>
					<?php endWhile; ?>
				</select>
			</div>
		  <button type="submit" class="btn btn-sm col-12 mt-3 btn-primary">Gerar mapa</button>
		</form>
	  </div>
	</div>
		
	<div class="container mt-3">
	  <div class="bg-light p-5 rounded">
		<h1>Relatórios</h1>
		<p class="lead">Use os filtros abaixo para gerar relatórios.</p>
		<form action="relatorio.php" method="get">
			<?php
			$sql = "SELECT 
			(SELECT data_atendimento FROM atendimento ORDER BY data_atendimento ASC LIMIT 1) AS min_data,
			(SELECT data_atendimento FROM atendimento ORDER BY data_atendimento DESC LIMIT 1) AS max_data;";
			$row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
			?>
			<div class="mb-3 row">
			  <div class="col-6">
				  <label class="form-check-label" for="inputData">Data inicial:</label>
				  <input class="form-control" type="date" name="dti" id="dtInicial"
				  min="<?= $row['min_data'] ?>" max="<?= $row['max_data'] ?>" value="<?= $row['min_data'] ?>">
			  </div>
			 
			  <div class="col-6">
				  <label class="form-check-label" for="inputData">Data final:</label>
				  <input class="form-control" type="date" name="dtf" id="dtFinal"
				  min="<?= $row['min_data'] ?>" max="<?= $row['max_data'] ?>" value="<?= $row['max_data'] ?>">
			  </div>
			</div>
			<?php
			$sql = 'select distinct a.fksetor, s.nome from atendimento a inner join setor s on (s.codigo = a.fksetor) where s.nome != "Teste"';
			$result = mysqli_query($conn, $sql);
			?>
			<div class="form-check form-switch">
			  <input class="form-check-input" type="checkbox" role="switch" id="RcheckSetor" checked>
			  <label class="form-check-label" for="RcheckSetor">Filtrar por setor</label>
				<select class="form-select" id="RselectSetor" name="setor">
					<option value="99">Todos</option>
					<?php while($row = mysqli_fetch_assoc($result)): ?>
					<option value='<?= $row["fksetor"] ?>'><?= $row["nome"] ?></option>
					<?php endWhile; ?>
				</select>
			</div>
			<?php
			$sql = 'SELECT DISTINCT bairro FROM endereco ORDER BY endereco.bairro ASC;';
			$result = mysqli_query($conn, $sql);
			?>
			<div class="form-check form-switch">
			  <input class="form-check-input" type="checkbox" role="switch" id="RcheckBairro">
			  <label class="form-check-label" for="RcheckBairro">Filtrar por bairro</label>
				<select class="form-select"id="RselectBairro" name="bairro" disabled>
					<option value="filtro">Todos</option>
					<?php while($row = mysqli_fetch_assoc($result)): ?>
					<option value='<?= $row["bairro"] ?>'><?= $row["bairro"] ?></option>
					<?php endWhile; ?>
				</select>
			</div>
		  <button type="submit" class="btn btn-sm col-12 mt-3 btn-primary">Gerar relatório</button>
		</form>
	  </div>
	</div>
	
		<div class="container mt-3">
		  <div class="bg-light rounded pb-3">
			<h1 class="text-center pt-3" id="topo">Lista de atendimentos</h1>
			<p class="text-center"><a class="text-center" href="#filtro-tabela">Ir para o final</a></p>
			<?php
			$sql = "SELECT a.codigo, a.data_atendimento, a.descricao, a.nis, setor.nome AS setor, CONCAT(endereco.logradouro, ' ', endereco.numero_residencia) AS endereco, tecnico.nome AS tecnico
			FROM atendimento a
			INNER JOIN setor ON setor.codigo = a.fksetor
			INNER JOIN endereco ON endereco.codigo = a.fkendereco
			INNER JOIN tecnico ON tecnico.codigo = a.fktecnico
			LIMIT 100";
			$result = mysqli_query($conn, $sql);
			?>	
			<table class="table table-sm table-striped" id="tabela" style="font-size: 12px;">
			  <thead>
				<tr>
					<th>Código</th>
					<th>Descrição</th>
					<th>Endereço</th>
					<th>Técnico</th>
					<th>Setor</th>
					<th>NIS</th>
					<th>Data</th>
				</tr>
			  </thead>
			  <tbody>
				<?php while($row = mysqli_fetch_assoc($result)): ?>
					<tr>
						<td><?= $row["codigo"] ?></td>
						<td><?= $row["descricao"] ?> </td>
						<td><?= $row["endereco"] ?></td>
						<td><?= $row["tecnico"] ?> </td>
						<td><?= $row["setor"] ?></td>
						<td><?= $row["nis"] ?> </td>
						<td><?= $row["data_atendimento"] ?></td>
					</tr>
				<?php endWhile; ?>
			  </tbody>
			</table>
			<div class="d-flex justify-content-center mb-3">
			    <div class="px-2 mx-2 bd-highlight"></div>
				<input class="form-control h-25 text-center" type="text" id="filtro-tabela" placeholder="Pesquisar na tabela...">
				<div class="px-2 mx-2 bd-highlight"></div>
				<br>
			</div>
			<p class="text-center">
				<a class="text-center" href="tabela.php">Ver a tabela completa</a>
				<a class="text-center" href="#topo">Voltar para o topo</a>
			</p>
			</div>
		</div>	
	<?php endif; ?>
  <?php endif; ?>
  </body>
  <script>
	//Filtros do mapa
	$(document).ready(function() {
	  const RcheckSetor = $('#RcheckSetor');
	  const RcheckBairro =  $('#RcheckBairro');
	  const checkSetor = $('#checkSetor');
	  const checkBairro =  $('#checkBairro');
	  const checkAtendimento = $('#checkAtendimento');
	  const selectSetor = $('#selectSetor');
	  const selectBairro = $('#selectBairro');
	  const RselectSetor = $('#RselectSetor');
	  const RselectBairro = $('#RselectBairro');
	  const selectAtendimento = $('#selectAtendimento');
	  
	  checkSetor.on('change', function() {
		if (checkSetor.prop('checked')) {
		  checkAtendimento.prop('checked', false);
		  checkBairro.prop('disabled', true);
		  checkBairro.prop('checked', false);
		  selectBairro.prop('disabled', true);
		  selectAtendimento.prop('disabled', true);
		  selectSetor.prop('disabled', false);
		}
		else {
		  checkBairro.prop('disabled', false);
		  checkAtendimento.prop('checked', true);
		  selectAtendimento.prop('disabled', false);
		  selectBairro.prop('disabled', false);
		  selectSetor.prop('disabled', true);
		}
	  });
	  
	  RcheckSetor.on('change', function() {
		if (RcheckSetor.prop('checked')) {
		  RcheckBairro.prop('disabled', true);
		  RcheckBairro.prop('checked', false);
		  RselectBairro.prop('disabled', true);
		  RselectSetor.prop('disabled', false);
		}
		else {
		  RcheckBairro.prop('disabled', false);
		  RselectBairro.prop('disabled', false);
		  RselectSetor.prop('disabled', true);
		}
	  });
	  
	  checkAtendimento.on('change', function() {
		if (checkAtendimento.prop('checked')) {
		  checkSetor.prop('checked', false);
		  selectSetor.prop('disabled', true);
		  selectAtendimento.prop('disabled', false);
		  checkBairro.prop('disabled', false);
		}
		else {
		  checkSetor.prop('checked', true);
		  selectAtendimento.prop('disabled', true);
		  selectSetor.prop('disabled', false);
		  checkBairro.prop('checked', false);
		  checkBairro.prop('disabled', true);
		  selectBairro.prop('disabled', true);
		}
	  });
	  
	  RcheckBairro.on('change', function() {
		if (RcheckBairro.prop('checked')) {
		  RselectBairro.prop('disabled', false);
		}
		else {
		  RselectBairro.prop('disabled', true);
		}
	  });
	  
	  checkBairro.on('change', function() {
		if (checkBairro.prop('checked')) {
		  selectBairro.prop('disabled', false);
		}
		else {
		  selectBairro.prop('disabled', true);
		}
	  });
	});

    // Barra de progresso
	const progressBar = document.getElementById('progress-bar');
    const locationHeading = document.getElementById('location-heading');
    let intervalId;

    function fillProgressBar(startPercentage, endPercentage) {
      let currentPercentage = startPercentage;
      const increment = (endPercentage - startPercentage) / 100;

      intervalId = setInterval(function() {
        if (currentPercentage >= endPercentage) {
          clearInterval(intervalId);
        } else {
          currentPercentage += increment;
          progressBar.style.width = `${currentPercentage}%`;
          progressBar.setAttribute('aria-valuenow', currentPercentage);
        }
      }, 100);
    }

    function showLocation(position) {
      // Stop filling the progress bar and set it to 100%
      clearInterval(intervalId);
      progressBar.style.width = '100%';
      progressBar.setAttribute('aria-valuenow', 100);

	fetch('geojson/get_lote.php?lat=' + position.coords.latitude + '&lon=' + position.coords.longitude)
	    .then(response => {
			if (response.ok) return response.json();
			else {
				const inputTexts = document.querySelectorAll('.input-text');
				for (let i = 0; i < inputTexts.length; i++) {
				  inputTexts[i].classList.add('is-invalid', 'form-control');
				}	
				document.querySelector('.input-group').innerHTML += '<span class="invalid-feedback">Não foi possível determinar a sua localização. Por favor preencha os dados manualmente.</span>';
				document.querySelector('.progress').style = "display: none";
			}
		})
		.then(data => {
				console.log(data);
				document.querySelector('#input-rua').value = data.properties.id_eixo_novo_numero;
				document.querySelector('#input-num').value = data.properties.novo_numero;
				document.querySelector('#botao-loc').remove();
				document.querySelector('#botao-sel').className = "btn btn-sm btn-primary col-12 mt-2" 
			}
		);
    }

    function showError(error) {
      console.error(error.message);
	}
	  
	  function checkLote(){
		progressBar.style.display = 'block';
		progressBar.style.width = '2%';
		fillProgressBar(2, 67);
		navigator.geolocation.getCurrentPosition(showLocation);
	  }
	  
	  //Popula as datalists
	  document.querySelector('#input-rua').addEventListener('focus', function() {
		  const datalistL = document.getElementById('logradouros');
		  datalistL.innerHTML = '';
		  fetch('geojson/enderecos.json')
			.then(response => response.json())
			.then(data => {
			  data[2].data.sort((a, b) => a.logradouro.localeCompare(b.logradouro));
			  data[2].data.forEach(item => {
				const option = document.createElement('option');
				option.value = item.logradouro;
				datalistL.appendChild(option);
			  });
			});
	  });
			
	  document.querySelector('#input-num').addEventListener('focus', function() {
		  const datalistN = document.getElementById('numeros');
		  datalistN.innerHTML = '';
		  fetch('api/enderecos?logradouro=' + document.querySelector('#input-rua').value)
			.then(response => response.json())
			.then(data => {
			  data.sort((a, b) => parseInt(a.numero_residencia) - parseInt(b.numero_residencia));
			  data.forEach(item => {
				const option = document.createElement('option');
				option.value = item.numero_residencia;
				datalistN.appendChild(option);
			  });
			});
		});
		
		function compareRowsAsc(row1, row2, index) {
		  var tdValue1 = row1.cells[index].textContent;
		  var tdValue2 = row2.cells[index].textContent;
		  return tdValue1.localeCompare(tdValue2);
		}

		function compareRowsDesc(row1, row2, index) {
		  var tdValue1 = row1.cells[index].textContent;
		  var tdValue2 = row2.cells[index].textContent;
		  return tdValue2.localeCompare(tdValue1);
		}

		function sortTable(columnIndex, ascending) {
		  var tbody = document.querySelector("#tabela tbody");
		  var rows = Array.from(tbody.querySelectorAll("tr"));
		  var compareRows = ascending ? compareRowsAsc : compareRowsDesc;
		  rows.sort(function(row1, row2) {
			return compareRows(row1, row2, columnIndex);
		  });
		  rows.forEach(function(row) {
			tbody.appendChild(row);
		  });
		}

		var ths = document.querySelectorAll("#tabela th");
		ths.forEach(function(th, index) {
		  th.addEventListener("click", function() {
			var isAscending = th.classList.contains("ascending");
			sortTable(index, !isAscending);
			th.classList.toggle("ascending", !isAscending);
			th.classList.toggle("descending", isAscending);
			th.querySelector(".arrow").classList.toggle("asc", !isAscending);
			th.querySelector(".arrow").classList.toggle("desc", isAscending);
		  });

		  // Adiciona a seta na coluna atual
		  var arrow = document.createElement("span");
		  arrow.className = "arrow";
		  th.appendChild(arrow);
		});
		
		$(document).ready(function(){
		  $("#filtro-tabela").on("keyup", function() {
			var value = $(this).val().toLowerCase();
			$("#tabela tbody tr").filter(function() {
			  $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		  });
		});
  </script>
 </html>