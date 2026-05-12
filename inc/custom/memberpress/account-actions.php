<?php
/**
 * Custom account page form handlers and action renderer.
 *
 * Profile/password POST handlers + the renderer for subscription action
 * confirmation screens (cancel / suspend / resume / update / upgrade).
 */

function theme_store_profile_errors( $user_id, $errors ) {
	$errors = array_values( array_filter( array_map( 'wp_strip_all_tags', (array) $errors ) ) );
	if ( empty( $errors ) ) {
		return;
	}

	set_transient( 'theme_profile_errors_' . absint( $user_id ), $errors, 5 * MINUTE_IN_SECONDS );
}

function theme_get_profile_errors( $user_id ) {
	$key    = 'theme_profile_errors_' . absint( $user_id );
	$errors = get_transient( $key );

	if ( false !== $errors ) {
		delete_transient( $key );
	}

	return is_array( $errors ) ? $errors : array();
}

add_action( 'admin_post_theme_account_subscription_action', 'theme_handle_account_subscription_action' );
function theme_handle_account_subscription_action() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/prijava/' ) );
		exit;
	}

	$action = isset( $_POST['subscription_action'] ) ? sanitize_key( wp_unslash( $_POST['subscription_action'] ) ) : '';
	$sub_id = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
	$nonce  = isset( $_POST['theme_account_subscription_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['theme_account_subscription_nonce'] ) ) : '';

	$redirect_url = theme_account_tab_url( 'subscription' );

	if ( ! in_array( $action, array( 'cancel', 'suspend', 'resume' ), true ) || ! wp_verify_nonce( $nonce, 'theme_account_subscription_' . $action . '_' . $sub_id ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'security', $redirect_url ) );
		exit;
	}

	try {
		$context = theme_get_account_subscription_context( $sub_id );
		if ( is_wp_error( $context ) ) {
			wp_safe_redirect( add_query_arg( 'account_error', $context->get_error_code(), $redirect_url ) );
			exit;
		}

		$sub = $context['sub'];
		$pm  = $context['gateway'];

		if ( ! theme_account_subscription_action_available( $action, $sub, $pm ) ) {
			wp_safe_redirect( add_query_arg( 'account_error', 'not_available', $redirect_url ) );
			exit;
		}

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
	} catch ( Throwable $e ) {
		theme_log_account_subscription_action_error( $action, $sub_id, $e );

		$message = theme_account_subscription_action_success_code( $action );
		if ( $message && theme_account_subscription_action_was_applied( $action, $sub_id ) ) {
			wp_safe_redirect( add_query_arg( 'account_message', $message, $redirect_url ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'account_error', 'action_failed', $redirect_url ) );
		exit;
	}
}

function theme_account_subscription_action_success_code( $action ) {
	$messages = array(
		'cancel'  => 'cancelled',
		'suspend' => 'suspended',
		'resume'  => 'resumed',
	);

	$action = sanitize_key( $action );

	return isset( $messages[ $action ] ) ? $messages[ $action ] : '';
}

function theme_account_subscription_action_was_applied( $action, $sub_id ) {
	if ( ! class_exists( 'MeprSubscription' ) ) {
		return false;
	}

	try {
		$sub = new MeprSubscription( absint( $sub_id ) );
		if ( empty( $sub->id ) ) {
			return false;
		}
	} catch ( Throwable $e ) {
		theme_log_account_subscription_action_error( $action, $sub_id, $e );
		return false;
	}

	$status = isset( $sub->status ) ? (string) $sub->status : '';

	if ( 'cancel' === $action ) {
		return MeprSubscription::$cancelled_str === $status;
	}

	if ( 'suspend' === $action ) {
		return MeprSubscription::$suspended_str === $status;
	}

	if ( 'resume' === $action ) {
		return MeprSubscription::$active_str === $status;
	}

	return false;
}

function theme_log_account_subscription_action_error( $action, $sub_id, Throwable $error ) {
	if ( ! function_exists( 'error_log' ) ) {
		return;
	}

	error_log(
		sprintf(
			'Zaher account subscription action failed: action=%s sub_id=%d error=%s message=%s',
			sanitize_key( $action ),
			absint( $sub_id ),
			get_class( $error ),
			$error->getMessage()
		)
	);
}

function theme_account_notice_text( $type, $code ) {
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

function theme_account_subscription_back_link( $url ) {
	?>
	<a class="account-page__back-link" href="<?php echo esc_url( $url ); ?>">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<path d="M19 12H5M11 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"></path>
		</svg>
		<span>Natrag</span>
	</a>
	<?php
}

function theme_render_account_subscription_action( $action, $sub_id ) {
	$action  = sanitize_key( $action );
	$context = theme_get_account_subscription_context( $sub_id );
	$back_url = theme_account_tab_url( 'subscription' );

	if ( is_wp_error( $context ) ) {
		?>
		<div class="account-page__card account-page__action-card">
			<?php theme_account_subscription_back_link( $back_url ); ?>
			<div class="account-page__message account-page__message--error"><?php echo esc_html( $context->get_error_message() ); ?></div>
		</div>
		<?php
		return;
	}

	$sub     = $context['sub'];
	$product = $context['product'];
	$pm      = $context['gateway'];
	$title   = $product && ! empty( $product->post_title ) ? $product->post_title : 'Pretplata';

	if ( ! theme_account_subscription_action_available( $action, $sub, $pm ) ) {
		?>
		<div class="account-page__card account-page__action-card">
			<?php theme_account_subscription_back_link( $back_url ); ?>
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
		<?php theme_account_subscription_back_link( $back_url ); ?>
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
						} catch ( Throwable $e ) {
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
				<input type="hidden" name="action" value="theme_account_subscription_action">
				<input type="hidden" name="subscription_action" value="<?php echo esc_attr( $action ); ?>">
				<input type="hidden" name="sub_id" value="<?php echo esc_attr( $sub->id ); ?>">
				<?php wp_nonce_field( 'theme_account_subscription_' . $action . '_' . $sub->id, 'theme_account_subscription_nonce' ); ?>
				<button class="button button--small <?php echo 'cancel' === $action ? 'button--danger' : ''; ?>" type="submit">
					<?php echo esc_html( $button_labels[ $action ] ); ?>
				</button>
				<a class="button button--small button--hollow" href="<?php echo esc_url( $back_url ); ?>">Odustani</a>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle password change from custom account page.
 */
add_action( 'admin_post_theme_change_password', 'theme_handle_password_change' );
function theme_handle_password_change() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['theme_password_nonce'] ) || ! wp_verify_nonce( $_POST['theme_password_nonce'], 'theme_change_password' ) ) {
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
add_action( 'admin_post_theme_update_profile', 'theme_handle_profile_update' );
function theme_handle_profile_update() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['theme_profile_nonce'] ) || ! wp_verify_nonce( $_POST['theme_profile_nonce'], 'theme_update_profile' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=nonce' ) );
		exit;
	}

	$user = wp_get_current_user();

	$first_name = '';
	if ( isset( $_POST['user_first_name'] ) ) {
		$first_name = sanitize_text_field( wp_unslash( $_POST['user_first_name'] ) );
	} elseif ( isset( $_POST['first_name'] ) ) {
		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
	}

	$last_name = '';
	if ( isset( $_POST['user_last_name'] ) ) {
		$last_name = sanitize_text_field( wp_unslash( $_POST['user_last_name'] ) );
	} elseif ( isset( $_POST['last_name'] ) ) {
		$last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
	}

	$email         = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
	$email_changed = $email !== $user->user_email;

	if ( $email === '' || ! is_email( $email ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=email' ) );
		exit;
	}

	$existing_id = email_exists( $email );
	if ( $existing_id && (int) $existing_id !== (int) $user->ID ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=exists' ) );
		exit;
	}

	$memberpress_errors = array();
	$mepr_current_user  = null;

	if ( class_exists( 'MeprUser' ) ) {
		$mepr_current_user = new MeprUser( $user->ID );
	}

	if ( class_exists( 'MeprUsersCtrl' ) ) {
		$memberpress_errors = MeprUsersCtrl::validate_extra_profile_fields( null, null, $mepr_current_user );
	}

	if ( class_exists( 'MeprUser' ) ) {
		$account_params = $_POST;
		$account_params['user_first_name'] = $first_name;
		$account_params['user_last_name']  = $last_name;
		$account_params['user_email']      = $email;

		$memberpress_errors = MeprUser::validate_account( $account_params, $memberpress_errors );
	}

	if ( class_exists( 'MeprHooks' ) ) {
		$memberpress_errors = MeprHooks::apply_filters( 'mepr-validate-account', $memberpress_errors, $mepr_current_user );
	} else {
		$memberpress_errors = apply_filters( 'mepr-validate-account', $memberpress_errors, $mepr_current_user );
	}

	if ( ! empty( $memberpress_errors ) ) {
		if ( function_exists( 'theme_store_profile_errors' ) ) {
			theme_store_profile_errors( $user->ID, $memberpress_errors );
		}

		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=memberpress' ) );
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

	if ( class_exists( 'MeprUsersCtrl' ) ) {
		MeprUsersCtrl::save_extra_profile_fields( $user->ID, true );
	}

	if ( class_exists( 'MeprUser' ) ) {
		$address_fields    = array();
		$address_submitted = false;
		foreach ( array( 'mepr-address-one', 'mepr-address-two', 'mepr-address-city', 'mepr-address-state', 'mepr-address-zip', 'mepr-address-country' ) as $address_key ) {
			$address_submitted             = $address_submitted || isset( $_POST[ $address_key ] );
			$address_fields[ $address_key ] = isset( $_POST[ $address_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $address_key ] ) ) : '';
		}

		$mepr_user = new MeprUser( $user->ID );
		if ( $address_submitted ) {
			$mepr_user->set_address( $address_fields );
		}

		if ( class_exists( 'MeprHooks' ) ) {
			if ( $email_changed ) {
				MeprHooks::do_action( 'mepr-update-new-user-email', $mepr_user );
			}

			MeprHooks::do_action( 'mepr-save-account', $mepr_user );
		}
	}

	wp_redirect( home_url( '/moj-racun/?tab=profile&profile_updated=1' ) );
	exit;
}
