<?php
/*
Template Name: Loop Catalog
*/

get_header();

$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg'; // Custom placeholder image URL

the_post();

$in_progress_ids   = array();
$in_progress_count = 0;
if ( is_user_logged_in() && function_exists( 'zaher_get_in_progress_program_ids' ) ) {
	$in_progress_ids = zaher_get_in_progress_program_ids( get_current_user_id() );
	if ( $in_progress_ids ) {
		$in_progress_ids = array_values(
			array_filter(
				array_map( 'absint', $in_progress_ids ),
				function ( $program_id ) {
					return $program_id
						&& get_post_status( $program_id ) === 'publish'
						&& get_post_type( $program_id ) === 'programs';
				}
			)
		);
		$in_progress_count = count( $in_progress_ids );
	}
}

$favorites_count = 0;
if ( is_user_logged_in() && function_exists( 'get_user_favorites' ) ) {
	$favorites_count = count(
		get_user_favorites(
			get_current_user_id(),
			get_current_blog_id(),
			array(
				'post_type' => 'programs',
				'status'    => 'publish',
			)
		)
	);
}

$search_value = isset( $_GET['katalog_search'] ) ? (string) wp_unslash( $_GET['katalog_search'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search_value = trim( $search_value );

?>

	<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<?php get_template_part( 'page-templates/loop-catalog/member-dashboard' ); ?>

		<div class="catalog-tabs" data-tabs>
			<div class="grid-container full">
					<div class="catalog-tabs__bar">
						<div class="tab-container" role="tablist" aria-label="Katalog filteri">
							<button class="tab active" type="button" role="tab" id="catalog-tab-kolekcije" aria-selected="true" aria-controls="kolekcije" data-tab-target="#kolekcije">
								Kolekcije
							</button>
							<?php /* <?php if ( is_user_logged_in() ) : ?>
								<button class="tab" type="button" role="tab" id="catalog-tab-continue" aria-selected="false" aria-controls="catalog-panel-continue" data-tab-target="#catalog-panel-continue">
									Nastavi <span class="tab__count">(<?php echo esc_html( $in_progress_count ); ?>)</span>
								</button>
							<?php endif; ?> */ ?>
							<button class="tab" type="button" role="tab" id="catalog-tab-favoriti" aria-selected="false" aria-controls="favoriti" data-tab-target="#favoriti">
								Moji favoriti <span class="tab__count">(<?php echo esc_html( $favorites_count ); ?>)</span>
							</button>
						</div>

					<?php /* <form class="member-dashboard__search catalog-tabs__search" method="get" role="search" autocomplete="off" data-search-endpoint="<?php echo esc_url( rest_url( 'wp/v2/programs' ) ); ?>" data-search-placeholder="<?php echo esc_url( $placeholder_url ); ?>">
						<div class="catalog-tabs__search-field">
							<label class="catalog-tabs__search-button" for="katalog-search">
								<?php echo zaher_lineicon_svg( 'search' ); ?>
								<span class="show-for-sr">Pretraži treninge</span>
							</label>
							<input id="katalog-search" class="catalog-tabs__search-input" type="search" name="katalog_search" value="<?php echo esc_attr( $search_value ); ?>" placeholder="Pretraži treninge..." autocomplete="off" autocapitalize="off" spellcheck="false" />
						</div>
						<div class="catalog-tabs__search-results" role="listbox" aria-label="Rezultati pretrage" aria-hidden="true"></div>
					</form> */ ?>
				</div>
			</div>

			<section id="kolekcije" class="content-container active all-videos" role="tabpanel" aria-labelledby="catalog-tab-kolekcije">
				<?php get_template_part( 'page-templates/loop-catalog/catalog' ); ?>
			</section>

			<?php /* <?php if ( is_user_logged_in() ) : ?>
				<section id="catalog-panel-continue" class="content-container in-progress-videos" role="tabpanel" aria-labelledby="catalog-tab-continue">
					<?php
					set_query_var( 'zaher_in_progress_ids', $in_progress_ids );
					get_template_part( 'page-templates/loop-catalog/in-progress' );
					?>
				</section>
			<?php endif; ?> */ ?>

			<section id="favoriti" class="content-container favorite-videos" role="tabpanel" aria-labelledby="catalog-tab-favoriti">
				<?php get_template_part( 'page-templates/loop-catalog/favorites' ); ?>
			</section>
		</div>

	</main>

<?php get_footer();
