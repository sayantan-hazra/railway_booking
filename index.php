<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION["user_id"])) {
	header("Location: user/home.php");
	exit;
}

$pageTitle = "Book Your Journey";
$stations = [];
$stationResult = $conn->query(
	"SELECT station_code, station_name, city
	 FROM stations
	 ORDER BY station_name"
);

if ($stationResult) {
	while ($station = $stationResult->fetch_assoc()) {
		$stations[] = $station;
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="RailEase makes railway ticket booking simple, clear and convenient.">
	<title><?php echo htmlspecialchars($pageTitle); ?> - RailEase</title>
	<link rel="stylesheet" href="assets/css/style.css">
	<style>
		.landing-body {
			min-height: 100vh;
			background:
				radial-gradient(circle at 86% 8%, rgba(147, 197, 253, 0.34), transparent 28%),
				linear-gradient(135deg, #eff6ff 0%, #f8fafc 52%, #ffffff 100%);
		}

		.landing-header,
		.landing-hero,
		.landing-features {
			width: min(1180px, calc(100% - 32px));
			margin: 0 auto;
		}

		.landing-header {
			padding: 22px 0;
		}

		.landing-nav {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 20px;
		}

		.landing-actions,
		.hero-actions {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 10px;
		}

		.landing-link {
			padding: 10px 14px;
			color: var(--text-secondary);
			font-size: 14px;
			font-weight: 700;
		}

		.landing-link:hover {
			color: var(--primary);
		}

		.landing-hero {
			padding: clamp(42px, 8vw, 92px) 0 72px;
		}

		.hero-grid {
			display: grid;
			grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
			align-items: center;
			gap: clamp(32px, 6vw, 88px);
		}

		.landing-hero h1 {
			max-width: 690px;
			margin: 0;
			font: 700 clamp(42px, 6vw, 70px) / 1.03 "Space Grotesk", sans-serif;
			letter-spacing: -2px;
		}

		.landing-hero h1 span {
			color: var(--primary);
		}

		.hero-copy {
			max-width: 560px;
			margin: 20px 0 0;
			color: var(--text-secondary);
			font-size: 17px;
			line-height: 1.7;
		}

		.hero-actions {
			margin-top: 28px;
		}

		.hero-actions .button,
		.landing-search-form .button {
			min-height: 46px;
		}

		.landing-search {
			padding: 28px;
			background: rgba(255, 255, 255, 0.9);
			border: 1px solid var(--border);
			border-radius: 16px;
			box-shadow: 0 22px 60px rgba(30, 58, 138, 0.13);
		}

		.landing-search h2 {
			margin: 0;
			font: 600 24px "Space Grotesk", sans-serif;
		}

		.landing-search-intro {
			margin: 8px 0 22px;
			color: var(--text-secondary);
			font-size: 14px;
		}

		.landing-search-form {
			display: grid;
			gap: 14px;
		}

		.landing-field label {
			display: block;
			margin-bottom: 7px;
		}

		.landing-field input {
			min-height: 46px;
		}

		.landing-search-form .button {
			width: 100%;
			margin-top: 4px;
		}

		.landing-features {
			padding-bottom: 72px;
		}

		.feature-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
		}

		.feature-item {
			padding: 22px;
			background: rgba(255, 255, 255, 0.72);
			border: 1px solid var(--border);
			border-radius: 12px;
		}

		.feature-icon {
			display: grid;
			place-items: center;
			width: 38px;
			height: 38px;
			margin-bottom: 16px;
			color: var(--primary);
			background: var(--light-blue);
			border-radius: 9px;
			font-weight: 700;
		}

		.feature-item h3 {
			margin: 0 0 7px;
			font: 600 17px "Space Grotesk", sans-serif;
		}

		.feature-item p {
			margin: 0;
			color: var(--text-secondary);
			font-size: 13px;
			line-height: 1.6;
		}

		.landing-footer {
			padding: 20px 16px;
			color: var(--text-secondary);
			background: rgba(255, 255, 255, 0.68);
			border-top: 1px solid var(--border);
			font-size: 12px;
			text-align: center;
		}

		.landing-footer p {
			margin: 0;
		}

		@media (max-width: 820px) {
			.hero-grid {
				grid-template-columns: 1fr;
			}

			.landing-hero {
				padding-top: 42px;
			}

			.landing-search {
				max-width: 620px;
			}
		}

		@media (max-width: 640px) {
			.landing-header,
			.landing-hero,
			.landing-features {
				width: min(100% - 24px, 1180px);
			}

			.landing-header {
				padding: 16px 0;
			}

			.landing-link {
				padding: 8px 6px;
			}

			.landing-actions .button {
				padding: 10px 12px;
			}

			.landing-hero h1 {
				font-size: 43px;
				letter-spacing: -1px;
			}

			.hero-copy {
				font-size: 15px;
			}

			.landing-search {
				padding: 22px 18px;
			}

			.feature-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>

<body class="landing-body">
	<header class="landing-header">
		<nav class="landing-nav" aria-label="Main navigation">
			<a href="index.php" class="brand" aria-label="RailEase home">
				<img class="brand-logo" src="assets/white_logo.png" alt="RailEase">
			</a>

			<div class="landing-actions">
				<a href="auth/login.php" class="landing-link">Log in</a>
				<a href="auth/signup.php" class="button button-primary">Create account</a>
			</div>
		</nav>
	</header>

	<main>
		<section class="landing-hero">
			<div class="hero-grid">
				<div>
					<p class="eyebrow">Railway travel, made simple</p>
					<h1>Go further with a journey that feels <span>easy.</span></h1>
					<p class="hero-copy">
						Find the right train, choose your coach and seat, and keep every booking in one clear place.
					</p>
					<div class="hero-actions">
						<a href="auth/signup.php" class="button button-primary">Start booking <span aria-hidden="true">&rarr;</span></a>
						<a href="auth/login.php" class="button button-muted">Sign in to RailEase</a>
					</div>
				</div>

				<section class="landing-search" aria-labelledby="search-heading">
					<p class="eyebrow">Plan your next trip</p>
					<h2 id="search-heading">Where are you going?</h2>
					<p class="landing-search-intro">Search available trains by station and travel date.</p>

					<form action="booking/search.php" method="GET" class="landing-search-form" id="trainSearchForm">
						<div class="landing-field">
							<label for="from">From</label>
							<select id="from" name="from" required>
								<option value="" selected disabled>Select departure station</option>
								<?php foreach ($stations as $station): ?>
									<option value="<?= htmlspecialchars($station['station_code']) ?>">
										<?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')') ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="landing-field">
							<label for="to">To</label>
							<select id="to" name="to" required>
								<option value="" selected disabled>Select destination station</option>
								<?php foreach ($stations as $station): ?>
									<option value="<?= htmlspecialchars($station['station_code']) ?>">
										<?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')') ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="landing-field">
							<label for="journey_date">Journey date</label>
							<input type="date" id="journey_date" name="journey_date" required>
						</div>

						<button type="submit" class="button button-primary">Search trains <span aria-hidden="true">&rarr;</span></button>
					</form>
				</section>
			</div>
		</section>

		<section class="landing-features" aria-label="RailEase features">
			<div class="feature-grid">
				<article class="feature-item">
					<div class="feature-icon" aria-hidden="true">01</div>
					<h3>Search with confidence</h3>
					<p>Compare available trains, departure times and journey duration in one view.</p>
				</article>

				<article class="feature-item">
					<div class="feature-icon" aria-hidden="true">02</div>
					<h3>Choose your seat</h3>
					<p>Select a coach and seat that works for the way you want to travel.</p>
				</article>

				<article class="feature-item">
					<div class="feature-icon" aria-hidden="true">03</div>
					<h3>Manage every booking</h3>
					<p>Keep your journeys, wallet and support requests together in your account.</p>
				</article>
			</div>
		</section>
	</main>

	<footer class="landing-footer">
		<p>&copy; <?php echo date("Y"); ?> RailEase. Simple journeys start here.</p>
	</footer>

	<script src="assets/js/script.js"></script>
</body>

</html>
