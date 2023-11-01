<?php
require('./fpdf/fpdf.php');
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

class PDF extends FPDF {
    function Footer() {
    	$this->SetY(-25);
    	$this->Image('./images/rodape.png', 0, $this->GetY(), $this->w, 25);
    }
}

if(isset($_POST['bairro'])){
	$equipamentos = [];
	if ($result = $conn->query("SELECT nome, codigo FROM setor")) {
		while ($row = $result->fetch_assoc()) {
			$equipamentos[] = [
				'nome' => $row['nome'],
				'codigo' => $row['codigo']
			];
		}
		$result->free();
	}

	$pdf = new PDF();
	$pdf->AddPage('P', 'A4');

	// Define o tamanho das células
	$w = 60;
	$h = 9;

	// Define a fonte para os cabeçalhos das colunas
	$pdf->SetFont('Arial', 'B', 12);

	// Define o total inicial como zero
	$total = 0;

		$pdf->Cell(200,40,'',0,0,'C');
		$pdf->Image('./images/cabecalho.png', 0,0,205);
		$pdf->Ln();
		
		$dti = $_GET['dti'];
		$dtf = $_GET['dtf'];

		setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(120, 5, utf8_decode(strftime('Itaperuçu, %d de %B de %Y')), 0, 1, 'C');
		$pdf->Ln();

		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('DE: Departamento de Vigilância Socioassistencial'), 'LRT');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('PARA: Gestão Secretaria Municipal de Assistência Social'), 'LR');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode("ASSUNTO: Atendimentos realizados no período entre " . $dti . ' e ' . $dtf . " em todos os"), 'LR');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Equipamentos lotados nesta Secretaria'), 'LRB');

		$pdf->SetFont('Arial', '', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Através do presente estamos:'), 'LRT');
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('( ) Encaminhando  ( ) Solicitando  (x) Informando  ( ) Comunicando'), 'LRB');

		$pdf->SetFont('Arial', '', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('        Prezados, venho por meio deste, informar a relação de atendimentos realizados nos setores'), 'LRT');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('da pasta da Secretaria, conforme instrumentais preenchidos pelo Equipamento. Segue relação'), 'LR');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('dos números abaixo:'), 'LRB');

		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, $h, "Equipamento", 1);
		$pdf->Cell(94, $h, utf8_decode("Número de Atendimentos"), 1);
		$pdf->Ln(); // Muda para a próxima linha

	// Loop for para imprimir as tabelas
	foreach ($equipamentos as $i){

		// Cria as células vazias nas laterais
		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(40); // Célula esquerda
		$pdf->Ln(); // Muda para a próxima linha

		// Define a fonte para os dados das células
		$pdf->SetFont('Arial', '', 12);

		// Consulta SQL para obter os dados da tabela atual
		$iv = intval($i['codigo']);
		$sql = "SELECT count(*) AS sum FROM atendimento WHERE fkSetor = $iv AND data_atendimento BETWEEN '" . $dti . "' AND '" . $dtf . "'";
		$result = $conn->query($sql);
		$sum = $result->fetch_assoc()['sum'];

		// Imprime as células com os dados da tabela atual
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, 7, utf8_decode($i['nome']), 1);
		$pdf->Cell(94, 7, $sum, 1, 0, 'L');
		// Adiciona o valor do total atual
		$total += $sum;

		// Muda para a próxima linha
		$pdf->Ln();

	}

	// Define a fonte para o total
	$pdf->SetFont('Arial', 'B', 12);

	// Imprime o total
	$pdf->Cell(1); // Célula esquerda
	$pdf->Cell(93, $h, "Total", 1);
	$pdf->Cell(94, $h, $total, 1, 0, 'L');
	$pdf->Ln();

	$pdf->SetFont('Arial', '', 12);

		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Sem mais para o momento, seguimos à disposição para eventuais esclarecimentos.'), 'LRT');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Atenciosamente,'), 'LR',1, 'C');
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, '', 'LR');
		$pdf->Ln();

		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Sabrina Willrich de Oliveira'), 'LR', 1, 'C');
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Diretora do Departamento de Vigilância Socioassistencial - CRESS 10426'), 'LR', 1, 'C');
		$pdf->Cell(1); // Célula esquerda
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, '', 'LRB');

	// Saída do PDF

	foreach ($equipamentos as $i){	
		$total = 0;
		$pdf->AddPage('P', 'A4');
			
		$sql = "SELECT descricao, count
					FROM (
						SELECT e.bairro AS descricao, COUNT(*) AS count 
						FROM atendimento a
						INNER JOIN endereco e ON e.codigo = a.fkEndereco
						WHERE a.data_atendimento BETWEEN '" . $dti . "' AND '" . $dtf . "'
						AND a.fkSetor = " . intval($i['codigo']) . "
						GROUP BY e.bairro					
					) AS result
				ORDER BY count DESC;";
		$result = $conn->query($sql);
		$data = [];

		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		
		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(40); // Célula esquerda
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', 'B', 12);
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, utf8_decode($i['nome']), 'LRT',1, 'C');
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, utf8_decode(strftime($dti . ' a ' . $dtf)), 'LR', 0, 'C');
			$pdf->Ln();
			
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, $h, "Bairro", 1);
		$pdf->Cell(94, $h, utf8_decode("Número de Atendimentos"), 1);
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', '', 9);

		foreach ($data as $row) {
			$bairro = $row['descricao'];
			$sum = $row['count'];

			// Imprime as células com os dados do bairro e o número correspondente
			// Imprime o total

			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(93, 4.5, utf8_decode($bairro), 1);
			$pdf->Cell(94, 4.5, $sum, 1, 0, 'L');
			// Adiciona o valor do total atual
			$total += $sum;

			// Muda para a próxima linha
			$pdf->Ln();
		}
		
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1);
		$pdf->Cell(93, 7, 'Total', 1);
		$pdf->Cell(94, 7, $total, 1, 0, 'L');
		$pdf->Ln();
	}

		$total = 0;
		$pdf->AddPage('P', 'A4');
			
		$sql = "SELECT descricao, count
					FROM (
						SELECT e.bairro AS descricao, COUNT(*) AS count 
						FROM atendimento a
						INNER JOIN endereco e ON e.codigo = a.fkEndereco
						WHERE a.data_atendimento BETWEEN '" . $dti . "' AND '" . $dtf . "'
						GROUP BY e.bairro					
					) AS result
					ORDER BY count DESC;";
		$result = $conn->query($sql);
		$data = [];

		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(40); // Célula esquerda
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', 'B', 12);
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, "Todos os equipamentos", 'LRT',1, 'C');
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, utf8_decode(strftime($dti . ' a ' . $dtf)), 'LR', 0, 'C');
			$pdf->Ln();
			
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, $h, "Bairro", 1);
		$pdf->Cell(94, $h, utf8_decode("Número de Atendimentos"), 1);
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', '', 9);

		foreach ($data as $row) {
			$bairro = $row['descricao'];
			$sum = $row['count'];

			// Imprime as células com os dados do bairro e o número correspondente
			// Imprime o total

			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(93, 4.5, utf8_decode($bairro), 1);
			$pdf->Cell(94, 4.5, $sum, 1, 0, 'L');
			// Adiciona o valor do total atual
			$total += $sum;

			// Muda para a próxima linha
			$pdf->Ln();
		}
		
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1);
		$pdf->Cell(93, 7, 'Total', 1);
		$pdf->Cell(94, 7, $total, 1, 0, 'L');
		$pdf->Ln();

	$pdf->Output();
}
else{
	$equipamentos = [];
	if ($result = $conn->query("SELECT nome, codigo FROM setor")) {
		while ($row = $result->fetch_assoc()) {
			$equipamentos[] = [
				'nome' => $row['nome'],
				'codigo' => $row['codigo']
			];
		}
		$result->free();
	}

	$pdf = new PDF();
	$pdf->AddPage('P', 'A4');

	// Define o tamanho das células
	$w = 60;
	$h = 9;

	// Define a fonte para os cabeçalhos das colunas
	$pdf->SetFont('Arial', 'B', 12);

	// Define o total inicial como zero
	$total = 0;

		$pdf->Cell(200,40,'',0,0,'C');
		$pdf->Image('./images/cabecalho.png', 0,0,205);
		$pdf->Ln();
		
		$dti = $_GET['dti'];
		$dtf = $_GET['dtf'];

		setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(120, 5, utf8_decode(strftime('Itaperuçu, %d de %B de %Y')), 0, 1, 'C');
		$pdf->Ln();

		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('DE: Departamento de Vigilância Socioassistencial'), 'LRT');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('PARA: Gestão Secretaria Municipal de Assistência Social'), 'LR');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode("ASSUNTO: Atendimentos realizados no período entre " . $dti . ' e ' . $dtf . " em todos os"), 'LR');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Equipamentos lotados nesta Secretaria'), 'LRB');

		$pdf->SetFont('Arial', '', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Através do presente estamos:'), 'LRT');
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('( ) Encaminhando  ( ) Solicitando  (x) Informando  ( ) Comunicando'), 'LRB');

		$pdf->SetFont('Arial', '', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('        Prezados, venho por meio deste, informar a relação de atendimentos realizados nos setores'), 'LRT');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('da pasta da Secretaria, conforme instrumentais preenchidos pelo Equipamento. Segue relação'), 'LR');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('dos números abaixo:'), 'LRB');

		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, $h, "Equipamento", 1);
		$pdf->Cell(94, $h, utf8_decode("Número de Atendimentos"), 1);
		$pdf->Ln(); // Muda para a próxima linha

	// Loop for para imprimir as tabelas
	foreach ($equipamentos as $i){

		// Cria as células vazias nas laterais
		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(40); // Célula esquerda
		$pdf->Ln(); // Muda para a próxima linha

		// Define a fonte para os dados das células
		$pdf->SetFont('Arial', '', 12);

		// Consulta SQL para obter os dados da tabela atual
		$iv = intval($i['codigo']);
		$sql = "SELECT count(*) AS sum FROM atendimento WHERE fkSetor = $iv AND data_atendimento BETWEEN '" . $dti . "' AND '" . $dtf . "'";
		$result = $conn->query($sql);
		$sum = $result->fetch_assoc()['sum'];

		// Imprime as células com os dados da tabela atual
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, 7, utf8_decode($i['nome']), 1);
		$pdf->Cell(94, 7, $sum, 1, 0, 'L');
		// Adiciona o valor do total atual
		$total += $sum;

		// Muda para a próxima linha
		$pdf->Ln();

	}

	// Define a fonte para o total
	$pdf->SetFont('Arial', 'B', 12);

	// Imprime o total
	$pdf->Cell(1); // Célula esquerda
	$pdf->Cell(93, $h, "Total", 1);
	$pdf->Cell(94, $h, $total, 1, 0, 'L');
	$pdf->Ln();

	$pdf->SetFont('Arial', '', 12);

		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Sem mais para o momento, seguimos à disposição para eventuais esclarecimentos.'), 'LRT');
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Atenciosamente,'), 'LR',1, 'C');
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, '', 'LR');
		$pdf->Ln();

		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Sabrina Willrich de Oliveira'), 'LR', 1, 'C');
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, utf8_decode('Diretora do Departamento de Vigilância Socioassistencial - CRESS 10426'), 'LR', 1, 'C');
		$pdf->Cell(1); // Célula esquerda
		$pdf->Ln();
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(187, $h, '', 'LRB');

	// Saída do PDF

	foreach ($equipamentos as $i){	
		$total = 0;
		$pdf->AddPage('P', 'A4');
			
		$sql = "SELECT descricao AS dsc, count(*) AS ct 
				FROM atendimento a 
				WHERE a.fkSetor = " . intval($i['codigo']) . "
				AND a.data_atendimento BETWEEN '" . $dti . "' AND '" . $dtf . "'
				GROUP BY dsc 
				ORDER BY ct DESC;";
		$result = $conn->query($sql);
		$data = [];

		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		
		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(40); // Célula esquerda
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', 'B', 12);
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, utf8_decode($i['nome']), 'LRT',1, 'C');
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, utf8_decode(strftime($dti . ' a ' . $dtf)), 'LR', 0, 'C');
			$pdf->Ln();
			
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, $h, "Bairro", 1);
		$pdf->Cell(94, $h, utf8_decode("Número de Atendimentos"), 1);
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', '', 9);

		foreach ($data as $row) {
			$bairro = $row['dsc'];
			$sum = $row['ct'];

			// Imprime as células com os dados do bairro e o número correspondente
			// Imprime o total

			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(93, 4.5, utf8_decode($bairro), 1);
			$pdf->Cell(94, 4.5, $sum, 1, 0, 'L');
			// Adiciona o valor do total atual
			$total += $sum;

			// Muda para a próxima linha
			$pdf->Ln();
		}
		
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1);
		$pdf->Cell(93, 7, 'Total', 1);
		$pdf->Cell(94, 7, $total, 1, 0, 'L');
		$pdf->Ln();
	}

		$total = 0;
		$pdf->AddPage('P', 'A4');
			
		$sql = "SELECT descricao AS dsc, count(*) AS ct 
				FROM atendimento a 
				WHERE a.data_atendimento BETWEEN '" . $dti . "' AND '" . $dtf . "'
				GROUP BY dsc 
				ORDER BY ct DESC;";
		$result = $conn->query($sql);
		$data = [];

		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		$pdf->Cell(40); // Célula esquerda
		$pdf->Cell(40); // Célula esquerda
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', 'B', 12);
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, "Todos os equipamentos", 'LRT',1, 'C');
			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(187, $h, utf8_decode(strftime($dti . ' a ' . $dtf)), 'LR', 0, 'C');
			$pdf->Ln();
			
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1); // Célula esquerda
		$pdf->Cell(93, $h, "Bairro", 1);
		$pdf->Cell(94, $h, utf8_decode("Número de Atendimentos"), 1);
		$pdf->Ln(); // Muda para a próxima linha
		$pdf->SetFont('Arial', '', 9);

		foreach ($data as $row) {
			$bairro = $row['dsc'];
			$sum = $row['ct'];

			// Imprime as células com os dados do bairro e o número correspondente
			// Imprime o total

			$pdf->Cell(1); // Célula esquerda
			$pdf->Cell(93, 4.5, utf8_decode($bairro), 1);
			$pdf->Cell(94, 4.5, $sum, 1, 0, 'L');
			// Adiciona o valor do total atual
			$total += $sum;

			// Muda para a próxima linha
			$pdf->Ln();
		}
		
		$pdf->SetFont('Arial', 'B', 12);
		$pdf->Cell(1);
		$pdf->Cell(93, 7, 'Total', 1);
		$pdf->Cell(94, 7, $total, 1, 0, 'L');
		$pdf->Ln();

	$pdf->Output();
}
?>

