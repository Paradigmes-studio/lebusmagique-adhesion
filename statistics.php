<?php
require_once("init.php");
require_once("get_login_info.php");
require_once("db/mAdhesionClient.php");

$manager = new mAdhesionClient($conn, $conf);
$available_years = $manager->get_available_years();
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if (!in_array($selected_year, $available_years) && !empty($available_years)) {
	$selected_year = $available_years[0];
}

$counts = $manager->count_by_month($selected_year);
$total = array_sum($counts);
$months_json = json_encode(array_values($counts));

$active_expired = $manager->count_active_expired($selected_year);
$newsletter = $manager->count_newsletter($selected_year);

$referral = $manager->count_by_referral_source($selected_year);
$referral_labels_map = [
	'passant' => 'En passant devant',
	'bouche_a_oreille' => 'Bouche à oreille',
	'instagram' => 'Instagram',
	'facebook' => 'Facebook',
	'site_web' => 'Site web',
	'autre' => 'Autre',
];
$referral_labels = [];
$referral_values = [];
foreach ($referral as $key => $count) {
	$referral_labels[] = $referral_labels_map[$key] ?? $key;
	$referral_values[] = $count;
}
$referral_labels_json = json_encode($referral_labels);
$referral_values_json = json_encode($referral_values);
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="mobile.css">
</head>
<body class="defaultback">
<div class="page">
<div class="page-title">Statistiques</div>

<form method="GET" action="statistics.php" style="text-align: center; margin-bottom: 16px;">
	<select name="year" onchange="this.form.submit()" style="width: auto; display: inline-block; padding: 10px 20px; border-radius: 1.5625rem; border: none; background: white; font-family: 'Barlow Condensed', sans-serif; font-size: 1em; font-weight: 600;">
		<?php foreach ($available_years as $year): ?>
			<option value="<?= $year ?>" <?= $year === $selected_year ? 'selected' : '' ?>><?= $year ?></option>
		<?php endforeach; ?>
	</select>
</form>

<!-- Chiffres clés -->
<div style="display: flex; gap: 8px; margin-bottom: 12px;">
	<div style="flex: 1; background: white; border-radius: 12px; padding: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
		<div style="font-size: 1.8em; font-weight: 700; color: #333;"><?= $total ?></div>
		<div style="font-size: 0.75em; color: #999; text-transform: uppercase; letter-spacing: 0.05em;">Inscriptions</div>
	</div>
	<div style="flex: 1; background: white; border-radius: 12px; padding: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
		<div style="font-size: 1.8em; font-weight: 700; color: #2ab934;"><?= $active_expired['actives'] ?></div>
		<div style="font-size: 0.75em; color: #999; text-transform: uppercase; letter-spacing: 0.05em;">Actives</div>
	</div>
	<div style="flex: 1; background: white; border-radius: 12px; padding: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
		<div style="font-size: 1.8em; font-weight: 700; color: #e6534e;"><?= $active_expired['expirees'] ?></div>
		<div style="font-size: 0.75em; color: #999; text-transform: uppercase; letter-spacing: 0.05em;">Expirées</div>
	</div>
</div>

<!-- Graphique inscriptions par mois -->
<div style="background: white; border-radius: 16px; padding: 16px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
	<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Inscriptions par mois</div>
	<canvas id="chart"></canvas>
</div>

<!-- Doughnuts : adhésions + newsletter -->
<div style="display: flex; gap: 12px; margin-bottom: 12px;">
	<div style="flex: 1; background: white; border-radius: 16px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
		<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Adhésions</div>
		<div style="max-width: 160px; margin: 0 auto;">
			<canvas id="chartActiveExpired"></canvas>
		</div>
	</div>
	<div style="flex: 1; background: white; border-radius: 16px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
		<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Newsletter</div>
		<div style="max-width: 160px; margin: 0 auto;">
			<canvas id="chartNewsletter"></canvas>
		</div>
	</div>
</div>

<?php if (!empty($referral_values)): ?>
<!-- Doughnut : source -->
<div style="background: white; border-radius: 16px; padding: 14px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
	<div style="font-size: 0.8em; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Comment tu nous as connus ?</div>
	<div style="max-width: 200px; margin: 0 auto;">
		<canvas id="chartReferral"></canvas>
	</div>
</div>
<?php endif; ?>

<p><a href="main.php" class="page-back">Retour</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chart'), {
	type: 'bar',
	data: {
		labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
		datasets: [{
			label: 'Inscriptions',
			data: <?= $months_json ?>,
			backgroundColor: '#e6534e',
			borderRadius: 6
		}]
	},
	options: {
		responsive: true,
		plugins: {
			legend: { display: false }
		},
		scales: {
			y: {
				beginAtZero: true,
				ticks: { stepSize: 1, font: { family: 'Barlow Condensed' } },
				grid: { color: '#f0ece0' }
			},
			x: {
				ticks: { font: { family: 'Barlow Condensed' } },
				grid: { display: false }
			}
		}
	}
});

var pctPlugin = {
	id: 'pctPlugin',
	afterDraw: function(chart) {
		var total = chart.data.datasets[0].data.reduce(function(a, b) { return a + b; }, 0);
		if (total === 0) return;
		var ctx = chart.ctx;
		var centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
		var centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
		var pct = Math.round(chart.data.datasets[0].data[0] / total * 100);
		ctx.save();
		ctx.font = 'bold 1.2em Barlow Condensed';
		ctx.fillStyle = '#333';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.fillText(pct + '%', centerX, centerY);
		ctx.restore();
	}
};

var doughnutOptions = {
	responsive: true,
	cutout: '60%',
	plugins: {
		legend: {
			position: 'bottom',
			labels: { font: { family: 'Barlow Condensed', size: 11 }, padding: 8, boxWidth: 12 }
		},
		tooltip: {
			callbacks: {
				label: function(ctx) {
					var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
					var pct = Math.round(ctx.raw / total * 100);
					return ctx.label + ' : ' + ctx.raw + ' (' + pct + '%)';
				}
			}
		}
	}
};

new Chart(document.getElementById('chartActiveExpired'), {
	type: 'doughnut',
	data: {
		labels: ['Actives', 'Expirées'],
		datasets: [{
			data: [<?= $active_expired['actives'] ?>, <?= $active_expired['expirees'] ?>],
			backgroundColor: ['#2ab934', '#e6534e'],
			borderWidth: 0
		}]
	},
	options: doughnutOptions,
	plugins: [pctPlugin]
});

new Chart(document.getElementById('chartNewsletter'), {
	type: 'doughnut',
	data: {
		labels: ['Abonnés', 'Non abonnés'],
		datasets: [{
			data: [<?= $newsletter['abonnes'] ?>, <?= $newsletter['non_abonnes'] ?>],
			backgroundColor: ['#4a90d9', '#ccc'],
			borderWidth: 0
		}]
	},
	options: doughnutOptions,
	plugins: [pctPlugin]
});

<?php if (!empty($referral_values)): ?>
var referralColors = ['#e6534e', '#4a90d9', '#f5a623', '#7b68ee', '#2ab934', '#ff6b81', '#ccc'];
new Chart(document.getElementById('chartReferral'), {
	type: 'doughnut',
	data: {
		labels: <?= $referral_labels_json ?>,
		datasets: [{
			data: <?= $referral_values_json ?>,
			backgroundColor: referralColors.slice(0, <?= count($referral_values) ?>),
			borderWidth: 0
		}]
	},
	options: {
		responsive: true,
		cutout: '60%',
		plugins: {
			legend: {
				position: 'bottom',
				labels: { font: { family: 'Barlow Condensed', size: 11 }, padding: 8, boxWidth: 12 }
			},
			tooltip: {
				callbacks: {
					label: function(ctx) {
						var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
						var pct = Math.round(ctx.raw / total * 100);
						return ctx.label + ' : ' + ctx.raw + ' (' + pct + '%)';
					}
				}
			}
		}
	}
});
<?php endif; ?>
</script>
</body>
</html>
