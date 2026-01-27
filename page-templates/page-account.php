<?php
/**
 * Template Name: Moj Račun (Custom)
 * Custom account page for members
 */

// Redirect non-logged-in users to login
if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/prijava/' ) );
	exit;
}

get_header();

$current_user = wp_get_current_user();
$display_name = $current_user->user_firstname ?: $current_user->display_name;

// Get MemberPress data
$subscriptions    = array();
$transactions     = array();
$has_memberpress  = class_exists( 'MeprUser' ) && class_exists( 'MeprTransaction' );
$profile_address  = array(
	'mepr-address-one'     => '',
	'mepr-address-two'     => '',
	'mepr-address-city'    => '',
	'mepr-address-state'   => '',
	'mepr-address-zip'     => '',
	'mepr-address-country' => '',
);

if ( $has_memberpress ) {
	$mepr_user     = new MeprUser( $current_user->ID );
	if ( class_exists( 'MeprSubscription' ) ) {
		$subscription_table = MeprSubscription::account_subscr_table(
			'created_at',
			'DESC',
			1,
			'',
			'any',
			50,
			false,
			array(
				'member'   => $current_user->user_login,
				'statuses' => array(
					MeprSubscription::$active_str,
					MeprSubscription::$suspended_str,
					MeprSubscription::$cancelled_str,
				),
			),
			array( 'id', 'user_id', 'product_id', 'subscr_id', 'status', 'created_at', 'expires_at', 'active' )
		);
		$subscriptions = $subscription_table['results'];
	}
	$transactions  = MeprTransaction::get_all_by_user_id(
		$current_user->ID,
		'created_at DESC',
		10 // Limit to last 10
	);
	$profile_address = array_merge( $profile_address, $mepr_user->full_address( false ) );
}

// Get logout URL
$logout_url = wp_logout_url( home_url() );
if ( class_exists( 'MeprUtils' ) ) {
	$logout_url = MeprUtils::logout_url();
}

// Current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'profile';
$allowed_tabs = array( 'profile', 'subscription', 'payments', 'password' );
if ( ! in_array( $current_tab, $allowed_tabs, true ) ) {
	$current_tab = 'profile';
}
?>

<main class="main account-page">
	<div class="grid-container">
		<div class="account-page__grid" data-account-tabs>

			<!-- Sidebar -->
			<aside class="account-page__sidebar">
				<div class="account-page__user">
					<div class="account-page__avatar">
						<?php echo get_avatar( $current_user->user_email, 80 ); ?>
					</div>
					<div class="account-page__user-info">
						<span class="account-page__user-name"><?php echo esc_html( $display_name ); ?></span>
						<span class="account-page__user-email"><?php echo esc_html( $current_user->user_email ); ?></span>
					</div>
				</div>

				<nav class="account-page__nav">
					<?php
					$tabs = array(
						'profile'      => array(
							'label' => 'Profil',
							'icon'  => 'user',
						),
						'subscription' => array(
							'label' => 'Pretplata',
							'icon'  => 'id-card',
						),
						'payments'     => array(
							'label' => 'Plaćanja',
							'icon'  => 'file',
						),
						'password'     => array(
							'label' => 'Lozinka',
							'icon'  => 'lock',
						),
					);
					?>

					<?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
						<?php $is_active = $tab_key === $current_tab; ?>
						<button
							type="button"
							class="account-page__nav-item <?php echo $is_active ? 'is-active' : ''; ?>"
							data-account-tab="<?php echo esc_attr( $tab_key ); ?>"
							id="account-tab-<?php echo esc_attr( $tab_key ); ?>"
							aria-controls="account-panel-<?php echo esc_attr( $tab_key ); ?>"
							aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
						>
							<?php echo zaher_lineicon_svg( $tab_data['icon'] ); ?>
							<?php echo esc_html( $tab_data['label'] ); ?>
						</button>
					<?php endforeach; ?>

				</nav>
			</aside>

			<!-- Main Content -->
			<div class="account-page__content">

				<?php $is_profile = 'profile' === $current_tab; ?>
				<div
					class="account-page__section <?php echo $is_profile ? 'is-active' : ''; ?>"
					id="account-panel-profile"
					data-account-panel="profile"
					aria-labelledby="account-tab-profile"
					<?php echo $is_profile ? '' : 'hidden'; ?>
				>
					<h1 class="account-page__title">Profil</h1>

					<div class="account-page__card">
						<form class="account-page__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="zaher_update_profile">
							<?php wp_nonce_field( 'zaher_update_profile', 'zaher_profile_nonce' ); ?>

							<div class="account-page__field">
								<label class="account-page__label" for="profile_first_name">Ime</label>
								<input class="account-page__input" type="text" id="profile_first_name" name="first_name" value="<?php echo esc_attr( $current_user->user_firstname ); ?>">
							</div>
							<div class="account-page__field">
								<label class="account-page__label" for="profile_last_name">Prezime</label>
								<input class="account-page__input" type="text" id="profile_last_name" name="last_name" value="<?php echo esc_attr( $current_user->user_lastname ); ?>">
							</div>
							<div class="account-page__field">
								<label class="account-page__label" for="profile_email">Email</label>
								<input class="account-page__input" type="email" id="profile_email" name="user_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" required>
							</div>
							<?php if ( $has_memberpress && class_exists( 'MeprOptions' ) ) : ?>
								<?php
								$mepr_options   = MeprOptions::fetch();
								$address_fields = $mepr_options->address_fields;
								if ( class_exists( 'MeprHooks' ) ) {
									$address_fields = MeprHooks::apply_filters( 'mepr_render_address_fields', $address_fields );
								} else {
									$address_fields = apply_filters( 'mepr_render_address_fields', $address_fields );
								}
								?>
								<?php if ( ! empty( $address_fields ) ) : ?>
									<div class="account-page__field">
										<span class="account-page__label">Adresa za naplatu</span>
										<div class="account-page__address-grid">
											<?php foreach ( $address_fields as $line ) : ?>
												<?php
												$field_key     = $line->field_key;
												$field_label   = wp_strip_all_tags( $line->field_name );
												$is_required   = ! empty( $line->required );
												$placeholder   = $field_label . ( $is_required ? '*' : '' );
												$field_value   = isset( $profile_address[ $field_key ] ) ? $profile_address[ $field_key ] : '';
												$required_attr = $is_required ? 'required' : '';
												?>
												<label class="show-for-sr" for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $field_label ); ?></label>
												<?php if ( 'countries' === $line->field_type && class_exists( 'MeprAppHelper' ) ) : ?>
													<?php
													echo MeprAppHelper::countries_dropdown(
														$field_key,
														$field_value,
														'account-page__input',
														$required_attr,
														false
													);
													?>
												<?php else : ?>
													<input
														class="account-page__input mepr-form-input"
														type="text"
														id="<?php echo esc_attr( $field_key ); ?>"
														name="<?php echo esc_attr( $field_key ); ?>"
														placeholder="<?php echo esc_attr( $placeholder ); ?>"
														value="<?php echo esc_attr( $field_value ); ?>"
														<?php echo $required_attr; ?>
													>
												<?php endif; ?>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
							<?php endif; ?>
							<div class="account-page__field">
								<label class="account-page__label">Član od</label>
								<div class="account-page__value"><?php echo esc_html( date_i18n( 'j. F Y.', strtotime( $current_user->user_registered ) ) ); ?></div>
							</div>
							<div class="account-page__field account-page__field--actions">
								<button type="submit" class="button button--small">Spremi</button>
							</div>

							<?php if ( isset( $_GET['profile_updated'] ) ) : ?>
								<div class="account-page__message account-page__message--success">
									Profil je uspješno spremljen.
								</div>
							<?php endif; ?>

							<?php if ( isset( $_GET['profile_error'] ) ) : ?>
								<div class="account-page__message account-page__message--error">
									<?php
										$error = sanitize_text_field( $_GET['profile_error'] );
										switch ( $error ) {
											case 'email':
												echo 'Unesite ispravnu email adresu.';
												break;
											case 'exists':
												echo 'Email adresa se već koristi.';
												break;
											case 'nonce':
												echo 'Došlo je do greške. Pokušajte ponovno.';
												break;
											default:
												echo 'Nije moguće spremiti profil.';
										}
										?>
								</div>
							<?php endif; ?>
						</form>
					</div>
				</div>

				<?php $is_subscription = 'subscription' === $current_tab; ?>
				<div
					class="account-page__section <?php echo $is_subscription ? 'is-active' : ''; ?>"
					id="account-panel-subscription"
					data-account-panel="subscription"
					aria-labelledby="account-tab-subscription"
					<?php echo $is_subscription ? '' : 'hidden'; ?>
				>
					<h1 class="account-page__title">Pretplata</h1>

					<?php if ( ! empty( $subscriptions ) ) : ?>
						<?php foreach ( $subscriptions as $subscription_row ) : ?>
							<?php
							$subscription_type = isset( $subscription_row->sub_type ) ? trim( $subscription_row->sub_type ) : 'subscription';
							$is_subscription   = 'subscription' === $subscription_type;
							$status_label    = 'Aktivna';
							$status_class    = 'active';
							$product_title   = 'Pretplata';
							$price_value     = '';
							$date_label      = '';
							$date_value      = '';
							$manage_url      = '';
							$can_manage      = false;

							if ( $is_subscription ) {
								$sub = new MeprSubscription( $subscription_row->id );
								if ( isset( $sub->status ) ) {
									if ( MeprSubscription::$cancelled_str === $sub->status ) {
										$status_label = 'Otkazana';
										$status_class = 'cancelled';
									} elseif ( MeprSubscription::$suspended_str === $sub->status ) {
										$status_label = 'Pauzirana';
										$status_class = 'pending';
									} elseif ( MeprSubscription::$pending_str === $sub->status ) {
										$status_label = 'Na čekanju';
										$status_class = 'pending';
									}
								}

								$product = $sub->product();
								if ( $product && ! empty( $product->post_title ) ) {
									$product_title = $product->post_title;
								}

								$latest_txn = $sub->latest_txn();
								if ( $latest_txn instanceof MeprTransaction ) {
									if ( class_exists( 'MeprTransactionsHelper' ) ) {
										$price_value = MeprTransactionsHelper::format_currency( $latest_txn );
									} else {
										$price_value = number_format( $latest_txn->total, 2, ',', '.' ) . ' €';
									}
								} elseif ( isset( $sub->price ) ) {
									$price_value = number_format( $sub->price, 2, ',', '.' ) . ' €';
								}

								if ( ! empty( $sub->next_billing_at ) ) {
									$date_label = 'Sljedeća naplata';
									$date_value = date_i18n( 'j. F Y.', strtotime( $sub->next_billing_at ) );
								} else {
									$date_label = 'Ističe';
									if ( empty( $sub->expires_at ) || MeprUtils::db_lifetime() === $sub->expires_at || stripos( $sub->expires_at, '0000-00' ) !== false ) {
										$date_value = 'Doživotno';
									} else {
										$date_value = date_i18n( 'j. F Y.', strtotime( $sub->expires_at ) );
									}
								}

								$can_manage = in_array(
									$sub->status,
									array(
										MeprSubscription::$active_str,
										MeprSubscription::$suspended_str,
										MeprSubscription::$pending_str,
									),
									true
								);

								if ( $can_manage && $sub->can( 'update-subscriptions' ) && method_exists( $sub, 'update_url' ) ) {
									$manage_url = $sub->update_url();
								} elseif ( $can_manage && class_exists( 'MeprOptions' ) ) {
									$mepr_options = MeprOptions::fetch();
									$manage_url   = $mepr_options->account_page_url( 'action=subscriptions' );
								} elseif ( $can_manage && class_exists( 'MeprUtils' ) ) {
									$manage_url = MeprUtils::get_account_url() . '?action=subscriptions';
								}
							} else {
								$txn = new MeprTransaction( $subscription_row->id );
								$product = $txn->product();
								if ( $txn->txn_type === MeprTransaction::$fallback_str ) {
									$group = $product ? $product->group() : false;
									if ( $group && $mepr_user->subscription_in_group( $group ) ) {
										continue;
									}
								}
								if ( $product && ! empty( $product->post_title ) ) {
									$product_title = $product->post_title;
								}

								if ( $txn->is_expired() ) {
									$status_label = 'Istekla';
									$status_class = 'lapsed';
								}

								if ( class_exists( 'MeprTransactionsHelper' ) ) {
									$price_value = MeprTransactionsHelper::format_currency( $txn );
								} else {
									$price_value = number_format( $txn->total, 2, ',', '.' ) . ' €';
								}

								$date_label = 'Ističe';
								if ( empty( $txn->expires_at ) || MeprUtils::db_lifetime() === $txn->expires_at || stripos( $txn->expires_at, '0000-00' ) !== false ) {
									$date_value = 'Doživotno';
								} else {
									$date_value = date_i18n( 'j. F Y.', strtotime( $txn->expires_at ) );
								}
							}
							?>
							<div class="account-page__card account-page__card--subscription">
								<div class="account-page__subscription-status">
									<span class="account-page__status-badge account-page__status-badge--<?php echo esc_attr( $status_class ); ?>">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</div>
								<h3 class="account-page__subscription-name"><?php echo esc_html( $product_title ); ?></h3>
								<div class="account-page__subscription-details">
									<?php if ( ! empty( $date_value ) ) : ?>
										<div class="account-page__subscription-detail">
											<span class="account-page__detail-label"><?php echo esc_html( $date_label ); ?>:</span>
											<span class="account-page__detail-value"><?php echo esc_html( $date_value ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $price_value ) ) : ?>
										<div class="account-page__subscription-detail">
											<span class="account-page__detail-label">Cijena:</span>
											<span class="account-page__detail-value"><?php echo esc_html( $price_value ); ?></span>
										</div>
									<?php endif; ?>
								</div>
								<?php if ( $is_subscription && $can_manage && ! empty( $manage_url ) ) : ?>
									<a href="<?php echo esc_url( $manage_url ); ?>" class="button button--small button--outline">
										Upravljaj pretplatom
									</a>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="empty-state empty-state--card">
							<div class="empty-state__icon">
								<?php echo zaher_lineicon_svg( 'crown' ); ?>
							</div>
							<h3 class="empty-state__title">Nema aktivne pretplate</h3>
							<p class="empty-state__text">Pretplatite se na jedan od naših planova i započnite vježbati s Pilates programima.</p>
							<div class="empty-state__action">
								<a href="<?php echo esc_url( home_url( '/loop/' ) ); ?>" class="button">Pregledaj planove</a>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<?php $is_payments = 'payments' === $current_tab; ?>
				<div
					class="account-page__section <?php echo $is_payments ? 'is-active' : ''; ?>"
					id="account-panel-payments"
					data-account-panel="payments"
					aria-labelledby="account-tab-payments"
					<?php echo $is_payments ? '' : 'hidden'; ?>
				>
					<h1 class="account-page__title">Povijest plaćanja</h1>

					<?php if ( ! empty( $transactions ) ) : ?>
						<div class="account-page__card">
							<table class="account-page__table">
								<thead>
									<tr>
										<th>Datum</th>
										<th>Opis</th>
										<th>Iznos</th>
										<th>Status</th>
										<?php if ( class_exists( 'MeprHooks' ) ) : ?>
											<?php MeprHooks::do_action( 'mepr_account_payments_table_header' ); ?>
										<?php endif; ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $transactions as $txn ) : ?>
										<?php
										$product = get_post( $txn->product_id );
										$status_label = 'complete' === $txn->status ? 'Plaćeno' : ucfirst( $txn->status );
										$status_class = 'complete' === $txn->status ? 'success' : 'pending';
										?>
										<tr>
											<td><?php echo esc_html( date_i18n( 'j.n.Y.', strtotime( $txn->created_at ) ) ); ?></td>
											<td><?php echo esc_html( $product ? $product->post_title : 'N/A' ); ?></td>
											<td><?php echo esc_html( number_format( $txn->total, 2, ',', '.' ) ); ?> €</td>
											<td><span class="account-page__status-badge account-page__status-badge--<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
											<?php if ( class_exists( 'MeprHooks' ) ) : ?>
												<?php MeprHooks::do_action( 'mepr_account_payments_table_row', $txn ); ?>
											<?php endif; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<div class="empty-state empty-state--card">
							<div class="empty-state__icon">
								<?php echo zaher_lineicon_svg( 'empty-file' ); ?>
							</div>
							<h3 class="empty-state__title">Nema transakcija</h3>
							<p class="empty-state__text">Ovdje će se prikazati vaša povijest plaćanja nakon prve kupnje.</p>
						</div>
					<?php endif; ?>
				</div>

				<?php $is_password = 'password' === $current_tab; ?>
				<div
					class="account-page__section <?php echo $is_password ? 'is-active' : ''; ?>"
					id="account-panel-password"
					data-account-panel="password"
					aria-labelledby="account-tab-password"
					<?php echo $is_password ? '' : 'hidden'; ?>
				>
					<h1 class="account-page__title">Promjena lozinke</h1>

					<div class="account-page__card">
						<form class="account-page__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="zaher_change_password">
							<?php wp_nonce_field( 'zaher_change_password', 'zaher_password_nonce' ); ?>

							<div class="account-page__form-field">
								<label for="current_password">Trenutna lozinka</label>
								<div class="account-page__input-group">
									<input type="password" id="current_password" name="current_password" required>
									<button type="button" class="account-page__password-toggle" data-password-toggle aria-label="Prikaži lozinku" aria-pressed="false">
										<span class="account-page__toggle-icon account-page__toggle-icon--show"><?php echo zaher_lineicon_svg( 'eye' ); ?></span>
										<span class="account-page__toggle-icon account-page__toggle-icon--hide"><?php echo zaher_lineicon_svg( 'eye-off' ); ?></span>
									</button>
								</div>
							</div>

							<div class="account-page__form-field">
								<label for="new_password">Nova lozinka</label>
								<div class="account-page__input-group">
									<input type="password" id="new_password" name="new_password" required minlength="8">
									<button type="button" class="account-page__password-toggle" data-password-toggle aria-label="Prikaži lozinku" aria-pressed="false">
										<span class="account-page__toggle-icon account-page__toggle-icon--show"><?php echo zaher_lineicon_svg( 'eye' ); ?></span>
										<span class="account-page__toggle-icon account-page__toggle-icon--hide"><?php echo zaher_lineicon_svg( 'eye-off' ); ?></span>
									</button>
								</div>
							</div>

							<div class="account-page__form-field">
								<label for="confirm_password">Potvrdi novu lozinku</label>
								<div class="account-page__input-group">
									<input type="password" id="confirm_password" name="confirm_password" required minlength="8">
									<button type="button" class="account-page__password-toggle" data-password-toggle aria-label="Prikaži lozinku" aria-pressed="false">
										<span class="account-page__toggle-icon account-page__toggle-icon--show"><?php echo zaher_lineicon_svg( 'eye' ); ?></span>
										<span class="account-page__toggle-icon account-page__toggle-icon--hide"><?php echo zaher_lineicon_svg( 'eye-off' ); ?></span>
									</button>
								</div>
							</div>

							<button type="submit" class="button">Spremi lozinku</button>
						</form>

						<?php if ( isset( $_GET['password_changed'] ) ) : ?>
							<div class="account-page__message account-page__message--success">
								Lozinka je uspješno promijenjena.
							</div>
						<?php endif; ?>

						<?php if ( isset( $_GET['password_error'] ) ) : ?>
							<div class="account-page__message account-page__message--error">
								<?php
									$error = sanitize_text_field( $_GET['password_error'] );
									switch ( $error ) {
										case 'mismatch':
											echo 'Nove lozinke se ne podudaraju.';
											break;
										case 'wrong':
											echo 'Trenutna lozinka nije ispravna.';
											break;
										default:
											echo 'Došlo je do greške.';
									}
									?>
								</div>
							<?php endif; ?>
						</div>
					</div>

			</div>
		</div>
	</div>
</main>

<?php get_footer(); ?>
