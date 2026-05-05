<?php
/**
 * Custom account page URLs.
 */
function zaher_account_page_url( $args = array() ) {
	$page = get_page_by_path( 'moj-racun' );
	$url  = $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/moj-racun/' );

	if ( ! empty( $args ) && is_array( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

function zaher_account_tab_url( $tab, $args = array() ) {
	$args        = is_array( $args ) ? $args : array();
	$args['tab'] = sanitize_key( $tab );

	return zaher_account_page_url( $args );
}

function zaher_account_subscription_action_url( $action, $sub_id, $args = array() ) {
	$args           = is_array( $args ) ? $args : array();
	$args['action'] = sanitize_key( $action );
	$args['sub']    = absint( $sub_id );

	return zaher_account_page_url( $args );
}

add_filter( 'mepr-account-page-permalink', 'zaher_use_custom_account_page_for_memberpress_links' );
function zaher_use_custom_account_page_for_memberpress_links( $url ) {
	return zaher_account_page_url();
}

add_filter( 'mepr-account-nav-home-link', 'zaher_memberpress_account_home_link' );
function zaher_memberpress_account_home_link( $url ) {
	return zaher_account_tab_url( 'profile' );
}

add_filter( 'mepr-account-nav-subscriptions-link', 'zaher_memberpress_account_subscriptions_link' );
function zaher_memberpress_account_subscriptions_link( $url ) {
	return zaher_account_tab_url( 'subscription' );
}

add_filter( 'mepr-account-nav-payments-link', 'zaher_memberpress_account_payments_link' );
function zaher_memberpress_account_payments_link( $url ) {
	return zaher_account_tab_url( 'payments' );
}

add_filter( 'mepr-account-nav-change-password', 'zaher_memberpress_account_password_link' );
add_filter( 'mepr-rl-change-password-url', 'zaher_memberpress_account_password_link' );
function zaher_memberpress_account_password_link( $url ) {
	return zaher_account_tab_url( 'password' );
}

/**
 * Redirect legacy MemberPress account navigation to the custom account tabs.
 * Keep operational subscription actions on the custom account page so gateway flows still work.
 */
add_action( 'template_redirect', 'zaher_redirect_legacy_memberpress_account_urls', 0 );
function zaher_redirect_legacy_memberpress_account_urls() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$request_args = array();
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$query = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			parse_str( $query, $request_args );
		}
	}
	$request_args = array_merge( $request_args, wp_unslash( $_GET ) );

	$is_custom_account_page = is_page( 'moj-racun' );
	$is_legacy_account_page = false;

	if ( class_exists( 'MeprOptions' ) ) {
		$mepr_options = MeprOptions::fetch();
		if ( is_object( $mepr_options ) && ! empty( $mepr_options->account_page_id ) ) {
			$is_legacy_account_page = is_page( (int) $mepr_options->account_page_id );
		}
	}

	if ( ! $is_custom_account_page && ! $is_legacy_account_page ) {
		return;
	}

	$action = isset( $request_args['action'] ) ? sanitize_key( $request_args['action'] ) : '';

	if ( '' === $action && $is_legacy_account_page ) {
		wp_safe_redirect( zaher_account_tab_url( 'profile' ), 302 );
		exit;
	}

	$tab_map = array(
		'home'          => 'profile',
		'account'       => 'profile',
		'subscriptions' => 'subscription',
		'payments'      => 'payments',
		'newpassword'   => 'password',
	);

	if ( isset( $tab_map[ $action ] ) ) {
		$args = array(
			'tab' => $tab_map[ $action ],
		);

		if ( 'home' === $action && isset( $request_args['message'] ) && 'password_updated' === sanitize_key( $request_args['message'] ) ) {
			$args['tab']              = 'password';
			$args['password_changed'] = 1;
		}

		if ( 'newpassword' === $action && isset( $request_args['error'] ) ) {
			$args['password_error'] = sanitize_key( $request_args['error'] );
		}

		wp_safe_redirect( zaher_account_page_url( $args ), 302 );
		exit;
	}

	$subscription_actions = array( 'update', 'upgrade', 'cancel', 'suspend', 'resume' );

	if ( $is_legacy_account_page && in_array( $action, $subscription_actions, true ) ) {
		$args = array(
			'action' => $action,
		);

		foreach ( array( 'sub', 'message', 'errors' ) as $key ) {
			if ( isset( $request_args[ $key ] ) ) {
				$args[ $key ] = sanitize_text_field( $request_args[ $key ] );
			}
		}

		wp_safe_redirect( zaher_account_page_url( $args ), 302 );
		exit;
	}
}

add_action( 'wp_enqueue_scripts', 'zaher_enqueue_memberpress_account_action_assets', 20 );
function zaher_enqueue_memberpress_account_action_assets() {
	if ( ! is_page( 'moj-racun' ) || ! isset( $_GET['action'] ) || 'update' !== sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		return;
	}

	if ( class_exists( 'MeprAccountCtrl' ) ) {
		$account_ctrl = new MeprAccountCtrl();
		$account_ctrl->enqueue_scripts( true );
	}
}

function zaher_get_account_subscription_context( $sub_id ) {
	if ( ! is_user_logged_in() || ! class_exists( 'MeprSubscription' ) ) {
		return new WP_Error( 'unavailable', 'Pretplata trenutno nije dostupna.' );
	}

	$sub_id = absint( $sub_id );
	if ( ! $sub_id ) {
		return new WP_Error( 'missing_subscription', 'Pretplata nije pronađena.' );
	}

	$sub = new MeprSubscription( $sub_id );
	if ( empty( $sub->id ) ) {
		return new WP_Error( 'missing_subscription', 'Pretplata nije pronađena.' );
	}

	if ( (int) $sub->user_id !== get_current_user_id() ) {
		return new WP_Error( 'forbidden', 'Nemaš pristup ovoj pretplati.' );
	}

	$product = method_exists( $sub, 'product' ) ? $sub->product() : null;
	$pm      = method_exists( $sub, 'payment_method' ) ? $sub->payment_method() : null;

	return array(
		'sub'     => $sub,
		'product' => $product,
		'gateway' => $pm,
	);
}

function zaher_account_subscription_action_available( $action, $sub, $pm = null ) {
	if ( ! $sub instanceof MeprSubscription ) {
		return false;
	}

	$action       = sanitize_key( $action );
	$mepr_options = class_exists( 'MeprOptions' ) ? MeprOptions::fetch() : null;
	$status       = isset( $sub->status ) ? $sub->status : '';

	switch ( $action ) {
		case 'update':
			return MeprSubscription::$pending_str !== $status
				&& MeprSubscription::$cancelled_str !== $status
				&& method_exists( $sub, 'can' )
				&& $sub->can( 'update-subscriptions' )
				&& is_object( $pm )
				&& method_exists( $pm, 'display_update_account_form' );

		case 'upgrade':
			$product = method_exists( $sub, 'product' ) ? $sub->product() : null;
			$group   = $product && method_exists( $product, 'group' ) ? $product->group() : false;

			return $group && method_exists( $group, 'buyable_products' ) && count( $group->buyable_products() ) >= 1;

		case 'cancel':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_cancel_subs )
				&& MeprSubscription::$active_str === $status
				&& is_object( $pm )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'cancel-subscriptions' )
				&& method_exists( $pm, 'process_cancel_subscription' );

		case 'suspend':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_suspend_subs )
				&& MeprSubscription::$active_str === $status
				&& ( ! method_exists( $sub, 'in_free_trial' ) || ! $sub->in_free_trial() )
				&& is_object( $pm )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'suspend-subscriptions' )
				&& method_exists( $pm, 'process_suspend_subscription' );

		case 'resume':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_suspend_subs )
				&& MeprSubscription::$suspended_str === $status
				&& is_object( $pm )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'suspend-subscriptions' )
				&& method_exists( $pm, 'process_resume_subscription' );
	}

	return false;
}

add_action( 'admin_post_zaher_account_subscription_action', 'zaher_handle_account_subscription_action' );
function zaher_handle_account_subscription_action() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/prijava/' ) );
		exit;
	}

	$action = isset( $_POST['subscription_action'] ) ? sanitize_key( wp_unslash( $_POST['subscription_action'] ) ) : '';
	$sub_id = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
	$nonce  = isset( $_POST['zaher_account_subscription_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zaher_account_subscription_nonce'] ) ) : '';

	$redirect_url = zaher_account_tab_url( 'subscription' );

	if ( ! in_array( $action, array( 'cancel', 'suspend', 'resume' ), true ) || ! wp_verify_nonce( $nonce, 'zaher_account_subscription_' . $action . '_' . $sub_id ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'security', $redirect_url ) );
		exit;
	}

	$context = zaher_get_account_subscription_context( $sub_id );
	if ( is_wp_error( $context ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', $context->get_error_code(), $redirect_url ) );
		exit;
	}

	$sub = $context['sub'];
	$pm  = $context['gateway'];

	if ( ! zaher_account_subscription_action_available( $action, $sub, $pm ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'not_available', $redirect_url ) );
		exit;
	}

	try {
		if ( 'cancel' === $action ) {
			$pm->process_cancel_subscription( $sub->id );
			$message = 'cancelled';
		} elseif ( 'suspend' === $action ) {
			$pm->process_suspend_subscription( $sub->id );
			$message = 'suspended';
		} else {
			$pm->process_resume_subscription( $sub->id );
			$message = 'resumed';
		}

		wp_safe_redirect( add_query_arg( 'account_message', $message, $redirect_url ) );
		exit;
	} catch ( Exception $e ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'action_failed', $redirect_url ) );
		exit;
	}
}

function zaher_account_notice_text( $type, $code ) {
	$code = sanitize_key( $code );

	if ( 'success' === $type ) {
		$messages = array(
			'cancelled' => 'Pretplata je otkazana.',
			'suspended' => 'Pretplata je zaustavljena.',
			'resumed'   => 'Pretplata je ponovno aktivna.',
		);

		return isset( $messages[ $code ] ) ? $messages[ $code ] : '';
	}

	$errors = array(
		'security'             => 'Sigurnosna provjera nije uspjela. Pokušaj ponovno.',
		'unavailable'          => 'Pretplata trenutno nije dostupna.',
		'missing_subscription' => 'Pretplata nije pronađena.',
		'forbidden'            => 'Nemaš pristup ovoj pretplati.',
		'not_available'        => 'Ova akcija trenutno nije dostupna za tvoju pretplatu.',
		'action_failed'        => 'Akciju nije moguće dovršiti. Pokušaj ponovno ili nam se javi.',
	);

	return isset( $errors[ $code ] ) ? $errors[ $code ] : 'Došlo je do greške. Pokušaj ponovno.';
}

function zaher_render_account_subscription_action( $action, $sub_id ) {
	$action  = sanitize_key( $action );
	$context = zaher_get_account_subscription_context( $sub_id );
	$back_url = zaher_account_tab_url( 'subscription' );

	if ( is_wp_error( $context ) ) {
		?>
		<div class="account-page__card account-page__action-card">
			<a class="account-page__back-link" href="<?php echo esc_url( $back_url ); ?>">Natrag na pretplatu</a>
			<div class="account-page__message account-page__message--error"><?php echo esc_html( $context->get_error_message() ); ?></div>
		</div>
		<?php
		return;
	}

	$sub     = $context['sub'];
	$product = $context['product'];
	$pm      = $context['gateway'];
	$title   = $product && ! empty( $product->post_title ) ? $product->post_title : 'Pretplata';

	if ( ! zaher_account_subscription_action_available( $action, $sub, $pm ) ) {
		?>
		<div class="account-page__card account-page__action-card">
			<a class="account-page__back-link" href="<?php echo esc_url( $back_url ); ?>">Natrag na pretplatu</a>
			<h2 class="account-page__action-title">Akcija nije dostupna</h2>
			<p class="account-page__action-text">Ova opcija trenutno nije dostupna za tvoju pretplatu.</p>
		</div>
		<?php
		return;
	}

	$action_titles = array(
		'update'  => 'Ažuriraj karticu',
		'upgrade' => 'Promijeni plan',
		'cancel'  => 'Otkaži pretplatu',
		'suspend' => 'Zaustavi pretplatu',
		'resume'  => 'Nastavi pretplatu',
	);
	?>
	<div class="account-page__card account-page__action-card account-page__action-card--<?php echo esc_attr( $action ); ?>">
		<a class="account-page__back-link" href="<?php echo esc_url( $back_url ); ?>">Natrag na pretplatu</a>
		<div class="account-page__action-header">
			<span class="account-page__label"><?php echo esc_html( $title ); ?></span>
			<h2 class="account-page__action-title"><?php echo esc_html( isset( $action_titles[ $action ] ) ? $action_titles[ $action ] : 'Upravljanje pretplatom' ); ?></h2>
		</div>

		<?php if ( 'update' === $action ) : ?>
			<div class="account-page__memberpress-form">
				<?php
				$errors = array();
				$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

				if ( isset( $_GET['errors'] ) ) {
					$errors[] = sanitize_text_field( wp_unslash( $_GET['errors'] ) );
				}

				if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
					$errors = method_exists( $pm, 'validate_update_account_form' ) ? $pm->validate_update_account_form( $errors ) : $errors;

					if ( empty( $errors ) && method_exists( $pm, 'process_update_account_form' ) ) {
						try {
							$pm->process_update_account_form( $sub->id );
							$message = 'Podaci za plaćanje su ažurirani.';
						} catch ( Exception $e ) {
							$errors[] = $e->getMessage();
						}
					}
				}

				$pm->display_update_account_form( $sub->id, $errors, $message );
				?>
			</div>
		<?php elseif ( 'upgrade' === $action ) : ?>
			<?php
			$group = $product && method_exists( $product, 'group' ) ? $product->group() : false;
			$plans = $group && method_exists( $group, 'products' ) ? $group->products() : array();
			$user  = method_exists( $sub, 'user' ) ? $sub->user() : null;
			?>
			<p class="account-page__action-text">Odaberi plan na koji želiš prijeći. MemberPress će na checkoutu obračunati promjenu plana.</p>
			<div class="account-page__plan-options">
				<?php $has_plan_options = false; ?>
				<?php foreach ( $plans as $plan ) : ?>
					<?php
					if ( ! $plan instanceof MeprProduct || (int) $plan->ID === (int) $product->ID || ! $plan->can_you_buy_me() ) {
						continue;
					}

					$has_plan_options = true;
					$terms = class_exists( 'MeprProductsHelper' ) ? MeprProductsHelper::product_terms( $plan, $user ) : '';
					?>
					<div class="account-page__plan-option">
						<div>
							<h3><?php echo esc_html( $plan->post_title ); ?></h3>
							<?php if ( $terms ) : ?>
								<p><?php echo esc_html( wp_strip_all_tags( $terms ) ); ?></p>
							<?php endif; ?>
						</div>
						<a class="button button--small" href="<?php echo esc_url( $plan->url() ); ?>">Odaberi</a>
					</div>
				<?php endforeach; ?>
				<?php if ( ! $has_plan_options ) : ?>
					<div class="account-page__message account-page__message--error account-page__message--inline">
						Trenutno nema dostupnih planova za promjenu.
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php
			$copy = array(
				'cancel'  => 'Nakon potvrde pretplata se više neće automatski obnavljati.',
				'suspend' => 'Nakon potvrde pretplata će biti zaustavljena.',
				'resume'  => 'Nakon potvrde pretplata će se ponovno aktivirati.',
			);
			$button_labels = array(
				'cancel'  => 'Da, otkaži pretplatu',
				'suspend' => 'Da, zaustavi pretplatu',
				'resume'  => 'Da, nastavi pretplatu',
			);
			?>
			<p class="account-page__action-text"><?php echo esc_html( $copy[ $action ] ); ?></p>
			<form class="account-page__action-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="zaher_account_subscription_action">
				<input type="hidden" name="subscription_action" value="<?php echo esc_attr( $action ); ?>">
				<input type="hidden" name="sub_id" value="<?php echo esc_attr( $sub->id ); ?>">
				<?php wp_nonce_field( 'zaher_account_subscription_' . $action . '_' . $sub->id, 'zaher_account_subscription_nonce' ); ?>
				<button class="button button--small <?php echo 'cancel' === $action ? 'account-page__danger-button' : ''; ?>" type="submit">
					<?php echo esc_html( $button_labels[ $action ] ); ?>
				</button>
				<a class="button button--small button--outline" href="<?php echo esc_url( $back_url ); ?>">Odustani</a>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle password change from custom account page.
 */
add_action( 'admin_post_zaher_change_password', 'zaher_handle_password_change' );
function zaher_handle_password_change() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['zaher_password_nonce'] ) || ! wp_verify_nonce( $_POST['zaher_password_nonce'], 'zaher_change_password' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=nonce' ) );
		exit;
	}

	$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
	$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
	$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

	$user = wp_get_current_user();

	// Verify current password
	if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=wrong' ) );
		exit;
	}

	// Check if new passwords match
	if ( $new_password !== $confirm_password ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=mismatch' ) );
		exit;
	}

	// Update password
	wp_set_password( $new_password, $user->ID );

	// Re-login the user
	wp_set_auth_cookie( $user->ID );

	wp_redirect( home_url( '/moj-racun/?tab=password&password_changed=1' ) );
	exit;
}

/**
 * Handle profile updates from custom account page.
 */
add_action( 'admin_post_zaher_update_profile', 'zaher_handle_profile_update' );
function zaher_handle_profile_update() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['zaher_profile_nonce'] ) || ! wp_verify_nonce( $_POST['zaher_profile_nonce'], 'zaher_update_profile' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=nonce' ) );
		exit;
	}

	$user = wp_get_current_user();

	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$email      = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

	if ( $email === '' || ! is_email( $email ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=email' ) );
		exit;
	}

	$existing_id = email_exists( $email );
	if ( $existing_id && (int) $existing_id !== (int) $user->ID ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=exists' ) );
		exit;
	}

	$display_name = trim( $first_name . ' ' . $last_name );
	if ( $display_name === '' ) {
		$display_name = $user->display_name;
	}

	$result = wp_update_user(
		array(
			'ID'           => $user->ID,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'user_email'   => $email,
			'display_name' => $display_name,
			'nickname'     => $display_name,
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=save' ) );
		exit;
	}

	if ( class_exists( 'MeprUser' ) ) {
		$address_fields = array(
			'mepr-address-one'     => isset( $_POST['mepr-address-one'] ) ? $_POST['mepr-address-one'] : '',
			'mepr-address-two'     => isset( $_POST['mepr-address-two'] ) ? $_POST['mepr-address-two'] : '',
			'mepr-address-city'    => isset( $_POST['mepr-address-city'] ) ? $_POST['mepr-address-city'] : '',
			'mepr-address-state'   => isset( $_POST['mepr-address-state'] ) ? $_POST['mepr-address-state'] : '',
			'mepr-address-zip'     => isset( $_POST['mepr-address-zip'] ) ? $_POST['mepr-address-zip'] : '',
			'mepr-address-country' => isset( $_POST['mepr-address-country'] ) ? $_POST['mepr-address-country'] : '',
		);

		$mepr_user = new MeprUser( $user->ID );
		$mepr_user->set_address( $address_fields );
	}

	wp_redirect( home_url( '/moj-racun/?tab=profile&profile_updated=1' ) );
	exit;
}
