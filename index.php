<?php
//Verificar o cookie
require 'config/config.php';
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

@$login = explode(':', $_COOKIE['auth'])[0];
@$senha = explode(':', $_COOKIE['auth'])[1];
				
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
$sql = "SELECT senha FROM tecnico WHERE login = '$login'";
	
@$bcrypt = mysqli_fetch_assoc(mysqli_query($conn, $sql))['senha'];

if(isset($_POST['limpar'])){ setcookie('auth', '', time()-3600); header("Refresh: 0"); }
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
		  <div class="bg-light rounded pb-3">
			<h1 class="text-center pt-3">Lista de atendimentos</h1>
			<?php
			$sql = "SELECT a.codigo, a.data_atendimento, a.descricao, a.nis, setor.nome AS setor, CONCAT(endereco.logradouro, ' ', endereco.numero_residencia) AS endereco, tecnico.nome AS tecnico
			FROM atendimento a
			INNER JOIN setor ON setor.codigo = a.fksetor
			INNER JOIN endereco ON endereco.codigo = a.fkendereco
			INNER JOIN tecnico ON tecnico.codigo = a.fktecnico;
			";
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
			</div>
			</div>
		</div>	
	<?php endif; ?>
	
  <?php else: header("Location: login.php"); ?>
  <?php endif; ?>
  </body>
  <script>
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
	    .then(response => response.json())
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
	
	  //Puxa o endereço da localização atual e atualiza a barra de progresso
	  function checkLote(){
	    navigator.permissions.query({ name: 'geolocation' }).then(function(result) {
		  if (result.state === 'granted') {
			navigator.geolocation.getCurrentPosition(showLocation);
		  } else if (result.state === 'prompt') {
			progressBar.style.display = 'block';
			progressBar.style.width = '2%';
			fillProgressBar(2, 67);
			navigator.geolocation.getCurrentPosition(showLocation, showError);
		  } else if (result.state === 'denied') {
			showError(new Error('User denied geolocation'));
		  }
		});
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