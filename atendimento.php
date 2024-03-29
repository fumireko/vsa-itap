<?php
ini_set('display_errors', 1);
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

$sql = "SELECT descricao FROM atendimento WHERE fkTecnico = $fkTecnico ORDER BY codigo DESC LIMIT 1;";
@$ultimoTipo = mysqli_fetch_assoc(mysqli_query($conn, $sql))['descricao'];

if(isset($_POST['limpar'])){
	setcookie('auth', '', time()-3600); 
	ob_start();
	header("Refresh: 0"); 
	ob_end_flush();
}

if (!isset($_COOKIE['auth']) || !($senha == $bcrypt || password_verify($senha, $bcrypt))) {
    ob_start();
    header("Location: login.php");
    ob_end_flush();
    exit; // Ensure that the script stops executing here
}

//Precisa desse trecho aqui pra jogar os dados do mapa no leaflet
if(isset($_POST['logradouro']) && isset($_POST['numero_predial']) && !empty($_POST['logradouro']) && !empty($_POST['numero_predial'])){
	$logradouro = $_POST['logradouro'];
	$numero_predial = $_POST['numero_predial'];
	$sql = "SELECT codigo FROM endereco WHERE logradouro = '$logradouro' and numero_residencia = $numero_predial";
	@$fkEndereco = mysqli_fetch_assoc(mysqli_query($conn, $sql))['codigo'];
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js?"></script>
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
    html, body {
    	height: 100%;
    	margin: 0;
    }
    body {
		padding: 0;
		margin: 0;
	}
	#map {
		height: 50%;
		width: auto;
	}
	.legend {
		text-align: left;
		line-height: 18px;
		color: #555;
	}
	.legend i {
		width: 18px;
		height: 18px;
		float: left;
		margin-right: 8px;
		opacity: 0.7;
	}
	.info {
		padding: 6px 8px;
		background: white;
		background: rgba(255,255,255,0.8);
		box-shadow: 0 0 15px rgba(0,0,0,0.2);
		border-radius: 5px;
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
		
		<?php if(!isset($nisError)): ?>
		<div id="map" class="m-2 bg-light p-4 rounded"></div>
		<?php endif; ?>

	<datalist id="atendimentos-setor"></datalist>
	
	<div class="container mt-2">
	  <div class="bg-light p-4 rounded">
		<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="fkTecnico" value="<?= $fkTecnico ?>">
			<input type="hidden" name="fkSetor" value="<?= $fkSetor ?>">
			<input type="hidden" name="fkEndereco" value="<?= $fkEndereco ?>">
			<input type="hidden" name="logradouro" value="<?= $logradouro ?>">
			<input type="hidden" name="numero_predial" value="<?= $numero_predial ?>">
			
			<?php if(isset($nisError)): ?>
			<div class="input-group">
				<select list="atendimentos-setor" class="input-text col-3" type="text" id="desc" name="descricao" placeholder="Descrição" value="<?= @$_POST['descricao'] ?>" required autocomplete="off"></select>
				<input class="form-control col-3 is-invalid" type="text" name="nis" id="nis" required autocomplete="off">
				<input class="input-text col-3" type="text" name="nome" placeholder="Nome completo" required autocomplete="off">
				<input class="input-text col-3" type="date" id="data_atendimento" name="data_atendimento" required>
				<span class="invalid-feedback"><?= $nisError ?></span>
			</div>
			<?php else: ?>
			<label for="">Endereço:</label>
			<input class="form-control" type="text" placeholder="<?= $logradouro ?> <?= $numero_predial ?>" disabled>
			
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
				<label class="form-check-label" style="font-size:14px" for="sem-nis">Atendimento sem NIS</label>
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
		  const datalistD = document.getElementsByTagName('select')[0]
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

		//Leaflet
        function zoomToFeature(e) {
       		map.fitBounds(e.target.getBounds());
       	}

		function style(feature) {
			if(feature.properties.novo_numero == '<?= @$numero_predial ?>'){
				return {
				fillColor: 'red',
				opacity: 1,
				color: 'red',
				fillOpacity: 0.5
				};
			}
			else{
				return {
					fillColor: 'grey',
					opacity: 1,
					color: 'grey',
					fillOpacity: 0.25
				};
			}
		}
		
		const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        	maxZoom: 19,
        	attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });

        const lotes = L.layerGroup();
		
		const bairros = L.layerGroup();

        const satellite = L.gridLayer.googleMutant({
        	maxZoom: 20,
        	type: "satellite",
        });
		
        const map = L.map('map', {
        	zoom: 12,
        	layers: [satellite, lotes],
		center: [-25.22123,-49.34584]
        });

        const cartoLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png', {
        	attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        	subdomains: 'abcd',
        	maxZoom: 20
        });

        fetch("./geojson/get_rua.php?rua=<?= @$logradouro ?>&time=<?= time() ?>").then(response => response.json())
        .then(data => {
            var geoJsonLayer = L.geoJSON(data).addTo(lotes);
            var geoJsonLayerWithOptions = L.geoJSON(data, {
                style: style,
                onEachFeature: function(feature, layer) {
					layer.on({click: zoomToFeature});
                    layer.bindTooltip("<b>" + feature.properties.id_eixo_novo_numero + "</b><br>" + feature.properties.novo_numero);
                }
            }).addTo(lotes);
			map.fitBounds(geoJsonLayer.getBounds());
        });
		
		fetch("./geojson/bairros.geojson?<?= time() ?>").then(response => response.json())
        .then(data => {
            var geoJsonLayer = L.geoJSON(data).addTo(bairros);
            var geoJsonLayerWithOptions = L.geoJSON(data, {
                style: style,
                onEachFeature: function(feature, layer) {
					layer.on({click: zoomToFeature});
                    layer.bindTooltip("<b>" + feature.properties.name + "</b>");
                }
            }).addTo(bairros);
        });

        const baseLayers = {
        	'Google Maps': satellite,
        	'OpenStreetMap': osm
        };

        const overlays = {
        	'Nomes': cartoLabels,
			'Lotes': lotes,
			'Bairros': bairros
        };
		
		function onLocationFound(e) {
			const radius = Math.round(e.accuracy / 2);

			L.marker(e.latlng).addTo(map)
				.bindPopup("Você está em um raio de " + radius + " metros deste ponto.").openPopup();
				
			map.fitBounds(e.target.getBounds());
		}

		function onLocationError(e) {
			alert(e.message);
		}

		map.on('locationfound', onLocationFound);
		map.on('locationerror', onLocationError);

		map.locate({setView: false, maxZoom: 16});

        const layerControl = L.control.layers(baseLayers, overlays, { collapsed: true }).addTo(map);
    </script>
	
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>

</html>
