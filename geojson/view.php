<?php
//Verificar o cookie
require '../config/config.php';
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
if(isset($_GET['lg']) && isset($_GET['n']) && !empty($_GET['lg']) && !empty($_GET['n'])){
	$logradouro = substr($_GET['lg'], 0, -1);
	$numero_predial = $_GET['n'];
	$sql = "SELECT codigo FROM endereco WHERE logradouro = '$logradouro' and numero_residencia = $numero_predial";
	@$fkEndereco = mysqli_fetch_assoc(mysqli_query($conn, $sql))['codigo'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <base target="_top">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title></title>

    <link rel="shortcut icon" type="image/x-icon" href="docs/images/favicon.ico" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js?"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=" async="" defer=""></script>
    <script src="https://unpkg.com/leaflet.gridlayer.googlemutant@latest/dist/Leaflet.GoogleMutant.js?"></script>

    <style>
    html, body {
    	height: 100%;
    	margin: 0;
    }
    body {
		padding: 0;
		margin: 0;
	}
	#map {
		height: 100%;
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
	<div id="map"></div>
</body>

<script>
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

fetch("./get_rua.php?rua=<?= @$logradouro ?>&time=<?= time() ?>").then(response => response.json())
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

fetch("./bairros.geojson?<?= time() ?>").then(response => response.json())
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


const layerControl = L.control.layers(baseLayers, overlays, { collapsed: true }).addTo(map);
</script>
</html>