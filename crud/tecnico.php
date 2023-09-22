<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
//Verificar o cookie
require '../config/config.php';
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
    header("Location: ../login.php");
    exit; // Ensure that the script stops executing here
}

$sql = "SELECT codigo, nome FROM setor";
$result = $conn->query($sql);
$setores = array();
while ($row = $result->fetch_assoc()) {
    $setores[$row["codigo"]] = $row["nome"];
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
      
  <?php if(!isset($_COOKIE['auth']) && !($senha = $bcrypt || password_verify($senha, $bcrypt))): header("Location: ../login.php");  ?>
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
			<h1 class="text-center pt-3" id="topo">Lista de técnicos</h1>
			<p class="text-center">
			<a href="../register.php" class="col-3 btn btn-sm btn-success">Cadastrar técnico</a>
			</p>
			<?php
			$sql = "SELECT * FROM tecnico";
			$result = mysqli_query($conn, $sql);
			?>	
			<table class="table table-sm table-striped" id="tabela" style="font-size: 12px;">
			  <thead>
				<tr>
					<th>Código</th>
					<th>Nome</th>
					<th>Login</th>
					<th>Setor</th>
					<th>Ativo</th>
					<th>Ações</th>
				</tr>
			  </thead>
			  <tbody>
				<?php while($row = mysqli_fetch_assoc($result)): ?>
					<tr>
						<td><?= $row["codigo"] ?></td>
						<td><?= $row["nome"] ?> </td>
						<td><?= $row["login"] ?></td>
						<td><?= $setores[$row["setor"]] ?></td>
						<td><?= $row["ativo"] ?> </td>
						<td class="d-flex justify-content-center">
						<a href="tecnico/editar.php?i=<?= $row['codigo'] ?>" class="col-4 btn btn-sm btn-primary">Editar</a>
						<a href="tecnico/remover.php?i=<?= $row['codigo'] ?>" class="col-4 btn btn-sm btn-danger">Desativar</a>
						</td>
					</tr>
				<?php endWhile; ?>
			  </tbody>
			</table>
				<div class="d-flex justify-content-center mb-3">
					<input class="form-control h-25 text-center" type="text" id="filtro-tabela" placeholder="Pesquisar na tabela...">
				</div>
				<div class="px-2 mx-2 text-center">
					<a href="../">Voltar para a página inicial</a>
				</div>
			</div>
		</div>	
	<?php endif; ?>
  <?php endif; ?>
  </body>
  <script>
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