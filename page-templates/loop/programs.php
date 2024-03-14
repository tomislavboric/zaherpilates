<section class="loop-programs section">

	<div class="grid-container">

		<header class="section__header section__header--small">
			<div class="section__subtitle">Krenimo!</div>
			<h2 class="section__title">Prijedlog treninga</h2>
			<div class="section__desc">
				<p>Kako bi ti maksimalno olakšali i skratili put do vježbanja kreirali smo ti prijedlog treninga za prva tri mjeseca</p>
			</div>
		</header>

		<main class="loop-programs__main">
			<p>Već nakon prva tri mjeseca na LOOPu primjetit ćeš benefite kao što su: <strong>izostanak bolova, vidljive promjene na tijelu, veće zadovoljstvo svojim izgledom i osjećajem u tijelu, bolju izdržljivost i snagu.</strong></p>
			<p>Naš prijedlog treninga za prva tri mjeseca možeš slijediti ili treninge možeš birati po vlastitim preferencama. Na platformi te čeka skoro 200 tjelovježbi pilatesa, snage, joge i HIIT. Bitno je da znaš u kojoj si fazi ciklusa i da trening biraš u skladu sa svojim energetskim nivoom.</p>
			<p>Početnica si i ne znaš odakle krenuti? Bez brige! Imamo posebnu kategoriju za tebe!</p>
			<p>Uživaj u treningu!</p>

			<?php /*
			$terms = get_terms( array(
				'taxonomy' => 'catalog',
				'hide_empty' => false,
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
				<div class="cards" style="padding: 30px 0">
					<?php foreach ( $terms as $term ) :

					// vars
					$image = get_field('image', $term);
					?>
						<div class="cards__item">
							<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
								<figure class="cards__figure">
									<img src="<?php echo $image['url']; ?>" alt="<?php echo $term->name; ?>">
									<div class="cards__count">
										<span class="material-icons">video_library</span>
										<?php echo $term->count; ?>
									</div>
								</figure>
								<div class="cards__header">
									<h3 class="cards__title"><?php echo $term->name; ?></h3>
								</div>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				No categories found.
			<?php endif; */ ?>

		</main>

		<footer class="section__footer">
			<div class="buttons">
				<?php /* <a class="button" href="<?php echo home_url(); ?>/programs/">Zaviri u katalog treninga na LOOPu</a> */ ?>
				<a class="button" href="<?php echo home_url(); ?>/programs/">Zaviri u katalog treninga koji su ti dostupni ODMAH nakon pristupa</a>
			</div>
			<p class="small" style="margin-top: 8px">Tu smo za tebe ako ćeš imati pitanja. Šalji ih na <a href="mailto:info@zaherpilates.ch">info@zaherpilates.ch</a></p>
		</footer>

	</div>
</section>
