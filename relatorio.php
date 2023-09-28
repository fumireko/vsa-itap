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

if(isset($_POST['limpar'])){
	setcookie('auth', '', time()-3600); 
	ob_start();
	header("Refresh: 0"); 
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
	.p-0 {
	  padding: 0rem 0rem !important;
	}
	@page {
      size: A4;
      margin: 0;
    }
    html,
    body {
      width: 210mm;
      height: 297mm;
      margin: 0;
      padding: 0;
    }
    .content {
      width: 100%;
      height: 100%;
      padding-left: 20mm;
	  padding-right: 20mm;
      box-sizing: border-box;
	  min-height: calc(100% - 2.5cm);
	  padding-bottom: 2.5cm;
    }	
	footer {
	  position: fixed;
	  bottom: 0;
	  left: 0;
	  width: 100%;
	  height: 2.5cm;
	  text-align: center;
	}
	</style>
  </head>
  <body>
  <?php if((isset($_COOKIE['auth']) && ($senha = $bcrypt || password_verify($senha, $bcrypt))) && $login === 'admin'): ?>
  
	<?php
	
	if(isset($_GET['setor'])){
		$setor = $_GET['setor'];
		$setorNome = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT nome from setor WHERE codigo = ' . $_GET['setor']))['nome'];
		if($setorNome === 'Gestão') $setorNome = '';
	}		
	
	$dti = $_GET['dti'];
	$dtf = $_GET['dtf'];
	
	if(isset($_GET['descricao']))
		$descricao = $_GET['descricao'];
	else	
		$descricao = "filtro";
	
	if(isset($_GET['bairro']))
		$bairro = $_GET['bairro'];
	else	
		$bairro = "filtro";
	
	if(isset($_GET['setor']) && $_GET['setor'] != 99){
		$setor = $_GET['setor'];
		$query = "SELECT descricao, COUNT(*) AS count 
				  FROM atendimento 
				  WHERE data_atendimento between '$dti' and '$dtf' and fksetor = $setor 
				  GROUP BY descricao 
				  UNION ALL 
				  SELECT 'TOTAL', SUM(count) 
				  FROM (SELECT COUNT(*) AS count FROM atendimento where data_atendimento between '$dti' and '$dtf' and fksetor = $setor GROUP BY descricao)
				  AS subquery 
				  ORDER BY CASE 
				  WHEN descricao = 'TOTAL' THEN 1 ELSE 0 END, count DESC;";
	}
	else if($descricao === "filtro" && $bairro != "filtro"){
		$query = "SELECT descricao, COUNT(*) AS count 
				  FROM atendimento a
				  INNER JOIN endereco e ON e.codigo = a.fkEndereco
				  WHERE e.bairro = '$bairro' AND a.data_atendimento BETWEEN '$dti' AND '$dtf'
				  GROUP BY descricao
				  UNION ALL
				  SELECT '$bairro', SUM(count)
				  FROM (SELECT COUNT(*) AS count FROM atendimento a INNER JOIN endereco e ON e.codigo = a.fkEndereco WHERE e.bairro = '$bairro' AND a.data_atendimento BETWEEN '$dti' AND '$dtf')
				  AS subquery
				  ORDER BY CASE
				  WHEN descricao = 'TOTAL' THEN 1 ELSE 0 END, count DESC;
				  ";
	}
	else if($descricao === "filtro" && !isset($_GET['setor'])){
		$setor = 99;
		$query = "
				SELECT descricao, count
				FROM (
					SELECT e.bairro AS descricao, COUNT(*) AS count 
					FROM atendimento a
					INNER JOIN endereco e ON e.codigo = a.fkEndereco
					WHERE a.data_atendimento BETWEEN '$dti' AND '$dtf'
					GROUP BY e.bairro
					
					UNION ALL
					
					SELECT 'TOTAL' AS descricao, SUM(subquery.count) AS count
					FROM (
						SELECT e.bairro AS descricao, COUNT(*) AS count 
						FROM atendimento a
						INNER JOIN endereco e ON e.codigo = a.fkEndereco
						GROUP BY e.bairro
					) AS subquery
				) AS result
				ORDER BY count DESC;
				";
	}
	else {
		$setor = 99;
		$query = "SELECT s.nome as descricao, COUNT(a.codigo) as count 
				FROM atendimento a 
				INNER JOIN setor s ON s.codigo = a.fkSetor
				WHERE a.data_atendimento BETWEEN '$dti' AND '$dtf'
				GROUP BY s.nome
				UNION ALL
				SELECT 'TOTAL', COUNT(a.codigo)
				FROM atendimento a 
				WHERE a.data_atendimento BETWEEN '$dti' AND '$dtf';";
	}
	$result = mysqli_query($conn, $query);
	?>
	
	<div class="content">
		<img src="images/cabecalho.png" class="img-fluid">
		<p class="text-center" style="font-size: 12px">Vigilância Socioassistencial - relação dos atendimentos de <?= $dti ?> a <?= $dtf ?>
		<?php if (empty($setorNome)): ?></p>
		<?php else: ?> no <?= $setorNome ?></p>
		<?php endif ?>
		
		<div class="mx-2" style="font-size: 10px">
			<table class="table border border-black">
				<th class="border border-black p-0">Atendimento</th>
				<th class="border border-black p-0">Total</th>
				
				<?php while($row = mysqli_fetch_assoc($result)): ?>
				<tr><td class="p-0 border border-black"><?= $row["descricao"] ?></td>
				<td class="p-0 border border-black"><?= $row["count"] ?></td></tr>
				<?php endWhile; ?>
				
			</table>
		</div>
	</div>
	
	<footer class="px-5">
		<img src="images/rodape.png" class="img-fluid">
	</footer>
	
  <?php else: header("Location: login.php"); ?>
  <?php endif; ?>
  </body>
  <script>
    window.onload = function() {
      window.print();
    };
  </script>
</html>