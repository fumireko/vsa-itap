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

if(isset($_POST['limpar'])){ setcookie('auth', '', time()-3600); header("Refresh: 0"); }

//Precisa desse trecho aqui pra jogar os dados do mapa no leaflet
if(isset($_POST['logradouro']) && isset($_POST['numero_predial']) && !empty($_POST['logradouro']) && !empty($_POST['numero_predial'])){
	$logradouro = $_POST['logradouro'];
	$numero_predial = $_POST['numero_predial'];
	$sql = "SELECT codigo FROM endereco WHERE logradouro = '$logradouro' and numero_residencia = $numero_predial";
	@$fkEndereco = mysqli_fetch_assoc(mysqli_query($conn, $sql))['codigo'];
}

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

		$endpoint_url = "http://localhost/api/atendimentos";

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
	
		<div class="container mt-2 justify-content-center">
		  <div class="bg-light p-4 rounded h-100">
			<h1>Registrar Atendimento</h1>
			
			<p class="lead">Verifique a localização atual e o lote marcado em <span class="text-danger">vermelho</span>.</p>
		  </div>
		</div>
		
		<?php if(!isset($nisError)): ?>
		<div id="map" class="mx-2"></div>
		<?php endif; ?>

	<datalist id="atendimentos-setor"></datalist>
	
	<div class="container mt-2">
	  <div class="bg-light p-4 rounded">
		<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
			<div class="input-group">
				<input type="hidden" name="fkTecnico" value="<?= $fkTecnico ?>">
				<input type="hidden" name="fkSetor" value="<?= $fkSetor ?>">
				<input type="hidden" name="fkEndereco" value="<?= $fkEndereco ?>">
				<input type="hidden" name="logradouro" value="<?= $logradouro ?>">
				<input type="hidden" name="numero_predial" value="<?= $numero_predial ?>">
				
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

</body>

</html>
