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
<html lang="en">
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
	<?php if(isset($_COOKIE['auth']) && ($senha = $bcrypt || password_verify($senha, $bcrypt))): ?>
	
	<div id="table-container"></div>

	<nav class="navbar fixed-bottom navbar-dark bg-dark pb-4 text-light">
		<div class="container-fluid row d-flex justify-content-center px-2 mx-2">
			<div class="col-2">
			  <label for="codigo-filter">Número:</label>
			  <select class="form-control" id="codigo-filter">
				<option value="">Todos</option>
			  </select>
			</div>
			<div class="col-2">
			  <label for="data-atendimento-filter">Data de Atendimento:</label>
			  <select class="form-control" id="data-atendimento-filter">
				<option value="">Todas</option>
			  </select>
			</div>
			<div class="col-2">
			  <label for="endereco-filter">Endereço:</label>
			  <input class="form-control" type="text" id="endereco-filter" name="endereco-filter" list="list-enderecos">
			  <datalist id="list-enderecos"></datalist>
			</div>
			<div class="col-2">
			  <label for="setor-filter">Setor:</label>
			  <select class="form-control" id="setor-filter">
				<option value="">Todos</option>
			  </select>
			</div>
			<div class="col-2">
			  <label for="tecnico-filter">Técnico:</label>
			  <select class="form-control" id="tecnico-filter">
				<option value="">Todos</option>
			  </select>
			</div>
			<div class="col-2">
			  <label for="descricao-filter">Descrição:</label>
			  <select class="form-control" id="descricao-filter">
				<option value="">Todas</option>
			  </select>
			</div>
			<button class="btn btn-sm btn-primary col-3 mt-2" id="filtro-endereco">Filtrar</button>
		</div>
	</nav>
	<?php else: header("Location: login.php"); ?>
	<?php endif; ?>
</body>
    <script>
	function addDatalistOption(datalistId, value) {
	  var datalist = $(datalistId);
	  if (datalist.find('option[value="' + value + '"]').length === 0) {
		$('<option>').attr('value', value).appendTo(datalist);
	  }
	}

	function addSelectOption(selectId, value) {
	  var select = $(selectId);
	  if (select.find('option[value="' + value + '"]').length === 0) {
		$('<option>').attr('value', value).text(value).appendTo(select);
	  }
	}
	
	function compareRowsAsc(row1, row2, index) {
	  var tdValue1 = $(row1).find('td').eq(index).text();
	  var tdValue2 = $(row2).find('td').eq(index).text();
	  return tdValue1.localeCompare(tdValue2);
	}

	function compareRowsDesc(row1, row2, index) {
	  var tdValue1 = $(row1).find('td').eq(index).text();
	  var tdValue2 = $(row2).find('td').eq(index).text();
	  return tdValue2.localeCompare(tdValue1);
	}

	function sortTable(columnIndex, ascending) {
	  var tbody = $('.table tbody');
	  var rows = tbody.find('tr').toArray();
	  var compareRows = ascending ? compareRowsAsc : compareRowsDesc;
	  rows.sort(function(row1, row2) {
		return compareRows(row1, row2, columnIndex);
	  });
	  $.each(rows, function(index, row) {
		tbody.append(row);
	  });
	}
	
	$(document).ready(function() {
	  $.ajax({
		url: '/api/atendimentos',
		type: 'GET',
		dataType: 'json',
		success: function(data) {
		  // Cria a tabela
		  var table = $('<table>');
		  table.addClass('table');
		  table.addClass('table-hover');
		  var thead = $('<thead>').appendTo(table);
		  var tbody = $('<tbody>').appendTo(table);
		  
		  // Cria as colunas da tabela
		  var columns = ['Número', 'Data', 'Endereço', 'Setor', 'Técnico', 'Descrição'];
		  var tr = $('<tr>').appendTo(thead);
		  for (var i = 0; i < columns.length; i++) {
			$('<th>').text(columns[i]).appendTo(tr);
		  }
		  
		  // Preenche a tabela com os dados
		  $.each(data, function(index, item) {
			var tr = $('<tr>').appendTo(tbody);
			$('<td>').text(item.codigo).appendTo(tr);
			$('<td>').text(item.data_atendimento).appendTo(tr);
			$('<td>').text(item.endereco).appendTo(tr);
			$('<td>').text(item.setor).appendTo(tr);
			$('<td>').text(item.tecnico).appendTo(tr);
			$('<td>').text(item.descricao).appendTo(tr);
		  });
		  
		  // Adiciona a tabela ao elemento HTML
		  $('#table-container').append(table);
		  
		  // Filtra os dados na tabela
		  $('select').on('change', function() {
			var codigo = $('#codigo-filter').val();
			var dataAtendimento = $('#data-atendimento-filter').val();
			var endereco = $('#endereco-filter').val();
			var setor = $('#setor-filter').val();
			var tecnico = $('#tecnico-filter').val();
			var descricao = $('#descricao-filter').val();
			
			$('tbody tr').each(function(index, item) {
			  var showRow = true;
			  
			  if (codigo && $(item).find('td:nth-child(1)').text() !== codigo) {
				showRow = false;
			  }
			  
			  if (dataAtendimento && $(item).find('td:nth-child(2)').text() !== dataAtendimento) {
				showRow = false;
			  }
			  
			  if (endereco && $(item).find('td:nth-child(3)').text() !== endereco) {
				showRow = false;
			  }
			  
			  if (setor && $(item).find('td:nth-child(4)').text() !== setor) {
				showRow = false;
			  }
			  
			  if (tecnico && $(item).find('td:nth-child(5)').text() !== tecnico) {
				showRow = false;
			  }
			  
			  if (descricao && $(item).find('td:nth-child(6)').text() !== descricao) {
				showRow = false;
			  }
			  
			  $(item).toggle(showRow);
			});
		  });
		  
		  // Preenche os selects com as opções de filtro
		  $.each(data, function(index, item) {
			addSelectOption('#codigo-filter', item.codigo);
			addSelectOption('#data-atendimento-filter', item.data_atendimento);
			addDatalistOption('#list-enderecos', item.endereco);
			addSelectOption('#setor-filter', item.setor);
			addSelectOption('#tecnico-filter', item.tecnico);
			addSelectOption('#descricao-filter', item.descricao);
		  });	    
			
		  var ths = $('.table th');
		  ths.each(function(index, th) {
			  $(th).on('click', function() {
				var isAscending = $(th).hasClass("ascending");
				sortTable(index, !isAscending);
				$(th).toggleClass("ascending", !isAscending);
				$(th).toggleClass("descending", isAscending);
				$(th).find(".arrow").toggleClass("asc", !isAscending);
				$(th).find(".arrow").toggleClass("desc", isAscending);
			  });
				  // Adiciona a seta na coluna atual
			  $('<span>').addClass('arrow').appendTo(th);
          });
		},
		error: function(jqXHR, textStatus, errorThrown) {
		  console.error(textStatus + ': ' + errorThrown);
		}
	  });
	});
	
	$('#filtro-endereco').on('click', function() {
	  var codigo = $('#codigo-filter').val();
	  var dataAtendimento = $('#data-atendimento-filter').val();
	  var endereco = $('#endereco-filter').val();
	  var setor = $('#setor-filter').val();
	  var tecnico = $('#tecnico-filter').val();
	  var descricao = $('#descricao-filter').val();

	  $('tbody tr').each(function(index, item) {
		var showRow = true;

		if (codigo && $(item).find('td:nth-child(1)').text() !== codigo) {
		  showRow = false;
		}

		if (dataAtendimento && $(item).find('td:nth-child(2)').text() !== dataAtendimento) {
		  showRow = false;
		}

		if (endereco && $(item).find('td:nth-child(3)').text() !== endereco) {
		  showRow = false;
		}

		if (setor && $(item).find('td:nth-child(4)').text() !== setor) {
		  showRow = false;
		}

		if (tecnico && $(item).find('td:nth-child(5)').text() !== tecnico) {
		  showRow = false;
		}

		if (descricao && $(item).find('td:nth-child(6)').text() !== descricao) {
		  showRow = false;
		}

		$(item).toggle(showRow);
	  });
	});
	</script>
</html>