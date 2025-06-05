<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

		<title><?php echo title('params-only') . ' [' . title(true) . ']';?></title>
		<link href="<?php echo fileUrl(variable('safeName')); ?>-icon.png" rel="icon">

		<?php cssTag(assetUrl('3p/reveal.css', COREASSETS));?>
		<?php cssTag(assetUrl('3p/reveal-white.css', COREASSETS));?>

		<!-- Theme used for syntax highlighted code -->
		<?php cssTag(assetUrl('presentation2.css', COREASSETS)); ?>
		<?php main::analytics(); ?>
	</head>
	<body id="<?php echo variable('all_page_params'); ?>">
		<!-- header thanks to: https://www.raymondcamden.com/2014/04/01/Adding-an-Absolutely-Positioned-Header-to-Revealjs -->
		<header id="topbar-wrapper">
			<div id="topbar" class="box-shadow"><?php if (!isset($_GET['iframe'])) {
				echo concatSlugs(['<a id="home-link" href="', currentUrl() , '">',
					variable('nl'), '				<img src="', fileUrl(variable('safeName') . '-icon.png'),
					'" alt="', variable('name'), '" height="30px"></a>', variable('nl') ], ''); }?>
			<span id="hash-id" style="color: #fff; font-size: 16px;">[slide-id]</span></div>
		</header>

		<div class="reveal">
			<div class="slides">
<section>
				<?php echo replaceItems(variable('deck'), ['<hr>' => variable('nl') . '</section><section>' . variable('nl')]); ?>
</section>

			</div>
		</div>

		<?php scriptTag(assetUrl('3p/reveal.js', COREASSETS));?>
		<script>
			// More info about initialization & config:
			// - https://revealjs.com/initialization/
			// - https://revealjs.com/config/
			Reveal.addEventListener('slidechanged', setHashId);

			Reveal.initialize({
				hash: true,
				// Learn about plugins: https://revealjs.com/plugins/
				// NOTE: making it superlight
				// plugins: [ RevealMarkdown, RevealHighlight, RevealNotes ]
			}).then(setMissingHashIds);

			function setMissingHashIds(event) {
				let slideIndex = 0;
				const slides = Reveal.getSlides();

				slides.forEach(function (slide) {
					slideIndex++;
					if (slide.id) return;
					var hidden = slide.querySelectorAll('input[type=hidden]');
					if (hidden.length) slide.id = hidden[0].value;
					else slide.id = '00' + slideIndex; //abs count
				});

				setHashId(event);
			}

			function setHashId(event) {
				document.getElementById('hash-id').innerText = '# ' + event.currentSlide.id.replaceAll('-', ' ');
			}
		</script>
	</body>
</html>
