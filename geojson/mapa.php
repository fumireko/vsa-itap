<!DOCTYPE html>
<html lang="en">

<head>
    <base target="_top">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title></title>

    <link rel="shortcut icon" type="image/x-icon" href="docs/images/favicon.ico" />
    <script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=" async="" defer=""></script>
    <script src="https://unpkg.com/leaflet.gridlayer.googlemutant@latest/dist/Leaflet.GoogleMutant.js"></script>

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
		width: 100vw;
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

    <div id='map'></div>

    <script>

	<?php
	require '../config/config.php';
	$db = new mysqli($servername, $username, $password, $dbname);
	
	if(isset($_GET['descricao']))
		$descricao = $_GET['descricao'];
	else	
		$descricao = "filtro";
	
	if(isset($_GET['setor']) && $_GET['setor'] != 99){
		$setor = $_GET['setor'];
		$query = "SELECT s.nome, e.bairro, COUNT(a.codigo) as max_val 
				  FROM atendimento a 
				  INNER JOIN endereco e ON e.codigo = a.fkEndereco 
				  INNER JOIN setor s ON a.fksetor = s.codigo
				  WHERE a.fksetor = $setor 
				  GROUP BY e.bairro ORDER BY max_val DESC LIMIT 1;";
	}
	else {
		$setor = 99;
		$query = "SELECT e.bairro, COUNT(a.codigo) as max_val 
				   FROM atendimento a 
				   INNER JOIN endereco e ON e.codigo = a.fkEndereco 
				   GROUP BY e.bairro ORDER BY max_val DESC LIMIT 1;";
	}
	$result = $db->query($query);
	$row = $result->fetch_assoc();
	?>
		<?php if(isset($row['nome'])): ?>
			var setor = "<?= $row['nome'] ?>";
		<?php else: ?>
			var setor = "Todos";
		<?php endif; ?>
		var min_val = 1;
		var max_val = <?= $row['max_val'] ?>;

		var intervalo = Math.ceil((max_val - min_val)/10);
		var limites_classe = [];

		for (var i = 0; i<10; i++){
			var lt_inferior = min_val + i*intervalo;
			var lt_superior = min_val + (i+1)*intervalo;
			limites_classe.push([lt_inferior, lt_superior]);
		}

		var cores_classe = ['#ffeda0', '#fed976', '#feb24c', '#fd8d3c', '#fc4e2a', '#e31a1c', '#bd0026', '#800026', '#6a112b', '#4d1928'];

	        function zoomToFeature(e) {
        		map.fitBounds(e.target.getBounds());
        	}

		function getColor(d){
			for(var i = 0; i<10; i++){
				if (d >= limites_classe[i][0] && d <= limites_classe[i][1]){
					return cores_classe[i];
				}
			}
			return '#f1dda7';
		}

		const bairros = L.layerGroup();

		const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		});

		const satellite = L.gridLayer.googleMutant({
			maxZoom: 24,
			type: "satellite",
		});

		const cartoLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			subdomains: 'abcd',
			maxZoom: 20
		});
		
		<?php if(($descricao != "filtro") && isset($_GET['descricao'])): ?>
		function style(feature) {
			return {
				fillColor: 'red',
				opacity: 1,
				color: 'red',
				fillOpacity: 0.8
			};
		}

		fetch("./get_lote.php?filtro=1&descricao=<?= $descricao ?>").then(response => response.json())
		.then(data => {
			var geoJsonLayer = L.geoJSON(data).addTo(bairros);
			map.fitBounds(geoJsonLayer.getBounds());
			var geoJsonLayerWithOptions = L.geoJSON(data, {
				style: style,
				onEachFeature: function(feature, layer) {
					layer.on({click: zoomToFeature});
					layer.bindTooltip("<b>" + feature.properties.id_eixo_novo_numero + " " + feature.properties.novo_numero + "</b><br>" + feature.properties.descricao);
				}
			}).addTo(bairros);
		});
		
		const map = L.map('map', {
			layers: [osm, bairros]
		});
		<?php endif; ?>
		<?php if($descricao === "filtro" && !isset($_GET['setor'])): ?>
		function style(feature) {
			return {
				fillColor: 'red',
				opacity: 1,
				color: 'red',
				fillOpacity: 0.8
			};
		}

		fetch("./get_lote.php?filtro=1").then(response => response.json())
		.then(data => {
			var geoJsonLayer = L.geoJSON(data).addTo(bairros);
			map.fitBounds(geoJsonLayer.getBounds());
			var geoJsonLayerWithOptions = L.geoJSON(data, {
				style: style,
				onEachFeature: function(feature, layer) {
					layer.on({click: zoomToFeature});
					layer.bindTooltip("<b>" + feature.properties.id_eixo_novo_numero + " " + feature.properties.novo_numero + "</b><br>" + feature.properties.descricao);
				}
			}).addTo(bairros);
		});
		
		const map = L.map('map', {
			layers: [osm, bairros]
		});
		<?php endif; ?>
		<?php if(isset($_GET['setor'])): ?>
		function style(feature) {
			return {
				fillColor: getColor(feature.properties.sum),
				opacity: 1,
				color: 'white',
				fillOpacity: 0.75
			};
		}

		fetch("./get_sum.php?setor=<?= $setor ?>").then(response => response.json())
		.then(data => {
			var geoJsonLayer = L.geoJSON(data).addTo(bairros);
			map.fitBounds(geoJsonLayer.getBounds());
			var geoJsonLayerWithOptions = L.geoJSON(data, {
				style: style,
				onEachFeature: function(feature, layer) {
					layer.on({click: zoomToFeature});
					layer.bindTooltip("<b>" + feature.properties.name + "</b><br>" + feature.properties.sum  + " atendimentos<br><b>" + setor + "</b>");
				}
			}).addTo(bairros);
		});
		const map = L.map('map', {
			layers: [satellite, bairros]
		});
		const legend = L.control({position: 'bottomright'});

		legend.onAdd = function(map) {
			const div = L.DomUtil.create('div', 'info legend');
			const labels = [];
			let from, to;

			for(let i=0; i< limites_classe.length; i++){
				from = limites_classe[i][0];
				to = limites_classe[i][1];
				labels.push(`<i style="background:${getColor(from+1)}"></i> ${from}${to ? `&ndash;${to}` : '+'}`);
			}

			div.innerHTML = labels.join('<br>');
			return div;
		};

		legend.addTo(map);
		<?php endif; ?>

		const baseLayers = {
			'Google Maps': satellite,
			'OpenStreetMap': osm
		};

		const overlays = {
			'Nomes': cartoLabels
		};

		const layerControl = L.control.layers(baseLayers, overlays, { collapsed: false }).addTo(map);
		document.title = setor;
    </script>

</body>

</html>
