<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPMLS_Admin_Settings {
	const PAGE_SLUG      = 'mpmls-settings';
	const SYNC_PAGE_SLUG = 'mpmls-sync';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_menu', array( $this, 'reorder_menu' ), 999 );
		add_action( 'wp_ajax_mpmls_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_mpmls_disconnect_api', array( $this, 'ajax_disconnect_api' ) );
		add_action( 'wp_ajax_mpmls_reconcile_active_members', array( $this, 'ajax_reconcile_active_members' ) );
		add_action( 'wp_ajax_mpmls_sync_expired_members', array( $this, 'ajax_sync_expired_members' ) );
		add_action( 'wp_ajax_mpmls_debug_subscriber', array( $this, 'ajax_debug_subscriber' ) );
		add_action( 'wp_ajax_mpmls_autosave_sync', array( $this, 'ajax_autosave_sync' ) );
		add_action( 'admin_post_mpmls_clear_logs', array( $this, 'handle_clear_logs' ) );
	}

	public function register_menu() {
		add_menu_page(
			'MP - MailerLite',
			'MP - MailerLite',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-email-alt2',
			80
		);

		add_submenu_page(
			self::PAGE_SLUG,
			'Settings',
			'Settings',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			'Sync',
			'Sync',
			'manage_options',
			self::SYNC_PAGE_SLUG,
			array( $this, 'render_sync_page' )
		);
	}

	public function reorder_menu() {
		global $menu;

		if ( empty( $menu ) || ! is_array( $menu ) ) {
			return;
		}

		$mpmls_item  = null;
		$mpmls_index = null;

		foreach ( $menu as $index => $item ) {
			if ( is_array( $item ) && isset( $item[2] ) && $item[2] === self::PAGE_SLUG ) {
				$mpmls_item  = $item;
				$mpmls_index = $index;
				break;
			}
		}

		if ( null === $mpmls_item ) {
			return;
		}

		$mailerlite_index = null;
		foreach ( $menu as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = isset( $item[0] ) ? trim( wp_strip_all_tags( $item[0] ) ) : '';
			$slug  = isset( $item[2] ) ? (string) $item[2] : '';

			if ( $label !== '' && 0 === strcasecmp( $label, 'MailerLite' ) ) {
				$mailerlite_index = $index;
				break;
			}

			if ( $mailerlite_index === null && $slug !== '' && false !== stripos( $slug, 'mailerlite' ) ) {
				$mailerlite_index = $index;
			}
		}

		if ( null === $mailerlite_index ) {
			return;
		}

		unset( $menu[ $mpmls_index ] );
		$menu = array_values( $menu );

		$mailerlite_index = null;
		foreach ( $menu as $index => $item ) {
			if ( is_array( $item ) && isset( $item[2] ) && (string) $item[2] === 'mailerlite' ) {
				$mailerlite_index = $index;
				break;
			}
		}
		if ( null === $mailerlite_index ) {
			foreach ( $menu as $index => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$label = isset( $item[0] ) ? trim( wp_strip_all_tags( $item[0] ) ) : '';
				$slug  = isset( $item[2] ) ? (string) $item[2] : '';
				if ( $label !== '' && 0 === strcasecmp( $label, 'MailerLite' ) ) {
					$mailerlite_index = $index;
					break;
				}
				if ( $mailerlite_index === null && $slug !== '' && false !== stripos( $slug, 'mailerlite' ) ) {
					$mailerlite_index = $index;
				}
			}
		}

		if ( null === $mailerlite_index ) {
			$menu[] = $mpmls_item;
			return;
		}

		array_splice( $menu, $mailerlite_index + 1, 0, array( $mpmls_item ) );
	}

	/* ------------------------------------------------------------------ */
	/*  Settings page (API key)                                           */
	/* ------------------------------------------------------------------ */

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings          = get_option( MPMLS_OPTION_KEY, array() );
		$api_key           = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$connection_status = get_option( 'mpmls_connection_status', array() );
		$connection_ok     = ! empty( $connection_status['ok'] )
			&& ! empty( $connection_status['key_hash'] )
			&& $connection_status['key_hash'] === md5( $api_key );
		$nonce = wp_create_nonce( 'mpmls_test_connection' );

		?>
		<div class="wrap mpmls-wrap">
			<style>
				.mpmls-wrap .form-table th { width: 260px; }
				.mpmls-wrap .form-table td { padding-top: 14px; padding-bottom: 14px; }
				.mpmls-wrap .mpmls-inline-actions { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
				.mpmls-wrap .mpmls-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; line-height: 1.6; vertical-align: middle; margin-left: 8px; }
				.mpmls-wrap .mpmls-badge--ok { background: #d4edda; color: #155724; }
				.mpmls-wrap .mpmls-badge--fail { background: #f8d7da; color: #721c24; }
			</style>
			<h1>MP - MailerLite &mdash; Settings</h1>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mpmls_api_key">MailerLite API key</label>
						<?php if ( $connection_ok ) : ?>
							<span class="mpmls-badge mpmls-badge--ok">Connected</span>
						<?php elseif ( $api_key !== '' ) : ?>
							<span class="mpmls-badge mpmls-badge--fail">Not connected</span>
						<?php endif; ?>
					</th>
					<td>
						<?php if ( $connection_ok ) : ?>
							<input type="password" id="mpmls_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" disabled />
							<div class="mpmls-inline-actions">
								<button type="button" class="button" id="mpmls-disconnect-api" data-nonce="<?php echo esc_attr( $nonce ); ?>">Disconnect</button>
								<span id="mpmls-test-result"></span>
							</div>
						<?php else : ?>
							<input type="password" id="mpmls_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="new-password" />
							<div class="mpmls-inline-actions">
								<button type="button" class="button button-primary" id="mpmls-test-connection" data-nonce="<?php echo esc_attr( $nonce ); ?>">Test connection</button>
								<span id="mpmls-test-result"></span>
							</div>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>

		<script>
		jQuery(function($){
			$('#mpmls-test-connection').on('click', function(){
				var $result = $('#mpmls-test-result');
				var apiKey = $.trim($('#mpmls_api_key').val());
				if (!apiKey) {
					$result.text('Please enter an API key.');
					return;
				}
				$result.text('Testing...');
				$.post(ajaxurl, {
					action: 'mpmls_test_connection',
					nonce: $(this).data('nonce'),
					api_key: apiKey
				}, function(response){
					if(response.success){
						$result.text('Success: ' + response.data.message);
						setTimeout(function(){ location.reload(); }, 600);
					} else {
						$result.text('Error: ' + response.data.message);
					}
				});
			});

			$('#mpmls-disconnect-api').on('click', function(){
				if (!confirm('Disconnect MailerLite API key?')) return;
				var $result = $('#mpmls-test-result');
				$result.text('Disconnecting...');
				$.post(ajaxurl, {
					action: 'mpmls_disconnect_api',
					nonce: $(this).data('nonce')
				}, function(response){
					if(response.success){
						location.reload();
					} else {
						$result.text('Error: ' + response.data.message);
					}
				});
			});
		});
		</script>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/*  Sync page (mapping, test, bulk sync, logs)                        */
	/* ------------------------------------------------------------------ */

	public function render_sync_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings           = get_option( MPMLS_OPTION_KEY, array() );
		$api_key            = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$expired_group_id   = isset( $settings['expired_group_id'] ) ? $this->normalize_group_id( $settings['expired_group_id'] ) : '';
		$logging_enabled    = ! empty( $settings['logging_enabled'] );
		$raw_mapping        = isset( $settings['mapping'] ) && is_array( $settings['mapping'] ) ? $settings['mapping'] : array();
		$mapping            = array();
		foreach ( $raw_mapping as $mid => $gid ) {
			$mapping[ $mid ] = $this->normalize_group_id( $gid );
		}

		$nonce = wp_create_nonce( 'mpmls_test_connection' );

		$rows = array();
		foreach ( $mapping as $membership_id => $group_id ) {
			$rows[] = array(
				'membership_id' => $membership_id,
				'group_id'      => $group_id,
			);
		}
		if ( empty( $rows ) ) {
			$rows[] = array( 'membership_id' => '', 'group_id' => '' );
		}

		$connection_status = get_option( 'mpmls_connection_status', array() );
		$connection_ok     = ! empty( $connection_status['ok'] )
			&& ! empty( $connection_status['key_hash'] )
			&& $connection_status['key_hash'] === md5( $api_key );

		$products       = $this->get_memberpress_products();
		$groups_result  = $this->get_mailerlite_groups( $api_key );
		$groups_error   = is_wp_error( $groups_result ) ? $groups_result->get_error_message() : '';
		$groups         = is_wp_error( $groups_result ) ? array() : $groups_result;

		$product_options = $this->render_product_options( $products, '' );
		$group_options   = $this->render_group_options( $groups, '' );

		$logs            = $this->get_logs();
		$event_filter    = isset( $_GET['mpmls_event'] ) ? sanitize_text_field( wp_unslash( $_GET['mpmls_event'] ) ) : '';
		$events          = $this->get_log_events();
		$active_counts    = $this->get_member_counts( $this->get_active_members_sql( 0, false ) );
		$inactive_counts  = $this->get_member_counts( $this->get_expired_members_sql( false ) );
		$active_breakdown = $this->get_membership_breakdown( $this->get_active_members_sql( 0, false ) );
		$diagnostics      = $this->get_diagnostics();

		?>
		<div class="wrap mpmls-wrap">
			<style>
				.mpmls-wrap .form-table th { width: 260px; }
				.mpmls-wrap .form-table td { padding-top: 14px; padding-bottom: 14px; }
				.mpmls-wrap .form-table .description { margin-top: 6px; }
				.mpmls-wrap .mpmls-inline-actions { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
				.mpmls-wrap .mpmls-table-wrap { overflow-x: auto; margin-top: 8px; }
				.mpmls-wrap .widefat { border-radius: 6px; overflow: hidden; }
				.mpmls-wrap .widefat thead th { background: #f6f7f7; }
				.mpmls-wrap .widefat th,
				.mpmls-wrap .widefat td { padding: 10px 12px; }
				.mpmls-wrap #mpmls-mapping-table td { vertical-align: middle; }
				.mpmls-wrap #mpmls-mapping-table th:nth-child(1),
				.mpmls-wrap #mpmls-mapping-table td:nth-child(1) { width: 240px; }
				.mpmls-wrap #mpmls-mapping-table th:nth-child(2),
				.mpmls-wrap #mpmls-mapping-table td:nth-child(2) { width: 240px; }
				.mpmls-wrap #mpmls-mapping-table th:nth-child(3),
				.mpmls-wrap #mpmls-mapping-table td:nth-child(3) { width: 1%; white-space: nowrap; }
				.mpmls-wrap #mpmls-mapping-table input,
				.mpmls-wrap #mpmls-mapping-table select { width: 100%; max-width: 260px; height: 32px; }
				.mpmls-wrap .mpmls-section-spacer { margin-top: 24px; }
				.mpmls-wrap .mpmls-logs-actions { display: flex; align-items: center; gap: 10px; margin: 10px 0 16px; }
				.mpmls-wrap .mpmls-quick-actions { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }

				/* Sync panel */
				.mpmls-wrap .mpmls-sync-panel { margin-top: 8px; padding: 16px 18px; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #2271b1; border-radius: 8px; max-width: 860px; }
				.mpmls-wrap .mpmls-sync-head { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
				.mpmls-wrap .mpmls-sync-head__text { flex: 1 1 320px; min-width: 260px; }
				.mpmls-wrap .mpmls-sync-head__text strong { display: block; margin-bottom: 2px; }
				.mpmls-wrap .mpmls-sync-head .description { margin: 0; }
				.mpmls-wrap .mpmls-dot { flex: 0 0 auto; width: 10px; height: 10px; margin-top: 5px; border-radius: 50%; background: #8c8f94; }
				.mpmls-wrap .mpmls-dot.is-ok { background: #00a32a; }
				.mpmls-wrap .mpmls-dot.is-idle { background: #2271b1; }
				.mpmls-wrap .mpmls-dot.is-warn { background: #d63638; }
				.mpmls-wrap .mpmls-dot.is-off { background: #8c8f94; }
				.mpmls-wrap .mpmls-dot.is-running { background: #2271b1; animation: mpmls-pulse 1.4s ease-in-out infinite; }
				@keyframes mpmls-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(34,113,177,.5); } 50% { box-shadow: 0 0 0 6px rgba(34,113,177,0); } }

				/* Progress */
				.mpmls-wrap .mpmls-progress { margin-top: 16px; }
				.mpmls-wrap .mpmls-steps { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
				.mpmls-wrap .mpmls-step { font-size: 12px; line-height: 1.6; padding: 3px 12px; border-radius: 999px; background: #f0f0f1; color: #646970; border: 1px solid #dcdcde; }
				.mpmls-wrap .mpmls-step.is-active { background: #f0f6fc; color: #0a4b78; border-color: #a7cced; font-weight: 600; }
				.mpmls-wrap .mpmls-step.is-done { background: #edfaef; color: #00733f; border-color: #a7e3b8; }
				.mpmls-wrap .mpmls-step.is-done::before { content: '\2713\00a0'; }
				.mpmls-wrap .mpmls-progress-track { height: 10px; background: #f0f0f1; border-radius: 999px; overflow: hidden; }
				.mpmls-wrap .mpmls-progress-bar { height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, #2271b1, #00a32a); transition: width .35s ease; }
				.mpmls-wrap .mpmls-progress-meta { display: flex; justify-content: space-between; gap: 12px; margin-top: 6px; font-size: 12px; color: #646970; }
				.mpmls-wrap .mpmls-progress-meta strong { color: #1d2327; font-variant-numeric: tabular-nums; }

				/* Result chips */
				.mpmls-wrap .mpmls-result { margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
				.mpmls-wrap .mpmls-chip { font-size: 12px; line-height: 1.6; padding: 3px 12px; border-radius: 999px; border: 1px solid; }
				.mpmls-wrap .mpmls-chip.is-synced { background: #edfaef; color: #00733f; border-color: #a7e3b8; }
				.mpmls-wrap .mpmls-chip.is-skipped { background: #f6f7f7; color: #50575e; border-color: #dcdcde; }
				.mpmls-wrap .mpmls-chip.is-errors { background: #fcf0f1; color: #b32d2e; border-color: #f0b4b6; }
				.mpmls-wrap .mpmls-chip.is-errors.is-zero { background: #f6f7f7; color: #50575e; border-color: #dcdcde; }
				.mpmls-wrap .mpmls-chip.is-message { background: #fcf0f1; color: #b32d2e; border-color: #f0b4b6; }

				/* Keep-open notice */
				.mpmls-wrap .mpmls-note { margin-top: 14px; padding: 9px 14px; border-radius: 4px; font-size: 13px; line-height: 1.6; background: #f6f7f7; border-left: 4px solid #8c8f94; color: #50575e; }
				.mpmls-wrap .mpmls-note.is-live { background: #fcf9e8; border-left-color: #dba617; color: #614f18; }
				.mpmls-wrap .mpmls-save-status { margin-left: 10px; font-size: 12px; color: #646970; }
			</style>
			<h1>MP - MailerLite &mdash; Sync</h1>

			<?php if ( ! $connection_ok ) : ?>
				<div class="notice notice-warning"><p>MailerLite API is not connected. <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">Go to Settings</a> to connect.</p></div>
			<?php endif; ?>

			<div class="notice notice-info"><p>Custom fields synced to MailerLite: <strong>name</strong>, <strong>last_name</strong>, <strong>membership_name</strong>, <strong>membership_expiry</strong>, <strong>signup_date</strong>, <strong>membership_status</strong>. Create these as custom fields in your MailerLite account (Subscribers &rarr; Fields) for full functionality.</p></div>

			<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Membership - Group mapping</th>
						<td>
							<div class="mpmls-table-wrap">
								<table class="widefat striped" id="mpmls-mapping-table">
								<thead>
									<tr>
										<th>MemberPress product ID</th>
										<th>MailerLite group ID</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $rows as $index => $row ) : ?>
										<tr>
											<td>
												<?php if ( ! empty( $products ) ) : ?>
													<select name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[mapping][<?php echo esc_attr( $index ); ?>][membership_id]">
														<?php echo $this->render_product_options( $products, $row['membership_id'] ); ?>
													</select>
												<?php else : ?>
													<input type="number" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[mapping][<?php echo esc_attr( $index ); ?>][membership_id]" value="<?php echo esc_attr( $row['membership_id'] ); ?>" />
												<?php endif; ?>
											</td>
											<td>
												<?php if ( ! empty( $groups ) ) : ?>
													<select name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[mapping][<?php echo esc_attr( $index ); ?>][group_id]">
														<?php echo $this->render_group_options( $groups, $row['group_id'] ); ?>
													</select>
												<?php else : ?>
													<input type="text" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[mapping][<?php echo esc_attr( $index ); ?>][group_id]" value="<?php echo esc_attr( $row['group_id'] ); ?>" />
												<?php endif; ?>
											</td>
											<td><button type="button" class="button mpmls-remove-row">Remove</button></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								</table>
							</div>
							<?php if ( $groups_error ) : ?>
								<p class="description">Could not load MailerLite groups: <?php echo esc_html( $groups_error ); ?></p>
							<?php endif; ?>
							<p><button type="button" class="button" id="mpmls-add-row">Add mapping</button><span class="mpmls-save-status" id="mpmls-save-status"></span></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mpmls-expired-group">Inactive group ID</label></th>
						<td>
							<?php if ( ! empty( $groups ) ) : ?>
								<select id="mpmls-expired-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[expired_group_id]" class="regular-text">
									<?php echo $this->render_group_options( $groups, $expired_group_id, true ); ?>
								</select>
							<?php else : ?>
								<input type="text" id="mpmls-expired-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[expired_group_id]" value="<?php echo esc_attr( $expired_group_id ); ?>" class="regular-text" />
							<?php endif; ?>
							<p class="description">Optional group ID for users whose subscription expires or is cancelled.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Logging</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[logging_enabled]" value="1" <?php checked( $logging_enabled ); ?> /> Enable logging</label>
						</td>
					</tr>
				</table>
			<div class="mpmls-sync-panel">
				<div class="mpmls-sync-head">
					<span class="mpmls-dot is-<?php echo esc_attr( $this->get_auto_sync_status_state() ); ?>"></span>
					<div class="mpmls-sync-head__text">
						<strong>Automatic sync</strong>
						<p class="description"><?php echo esc_html( $this->get_auto_sync_status_text() ); ?></p>
					</div>
					<button type="button" class="button button-primary" id="mpmls-sync-now" data-nonce="<?php echo esc_attr( $nonce ); ?>">Sync now</button>
				</div>

				<div class="mpmls-progress" id="mpmls-progress" style="display:none;">
					<div class="mpmls-steps">
						<span class="mpmls-step" data-step="1">1. Active members &rarr; plan groups</span>
						<span class="mpmls-step" data-step="2">2. Churned members &rarr; inactive group</span>
					</div>
					<div class="mpmls-progress-track"><div class="mpmls-progress-bar" id="mpmls-progress-bar"></div></div>
					<div class="mpmls-progress-meta">
						<span id="mpmls-progress-label">Starting&hellip;</span>
						<strong id="mpmls-progress-pct">0%</strong>
					</div>
				</div>

				<div class="mpmls-result" id="mpmls-sync-result"></div>

				<p class="mpmls-note" id="mpmls-sync-note">Everything syncs automatically on membership changes and every night at 03:30 &mdash; that runs on the server, so no browser window needs to stay open. &ldquo;Sync now&rdquo; instead runs in this tab: <strong>keep it open until it finishes.</strong> Closing it early only stops the run &mdash; nothing breaks, and you can safely start it again.</p>
			</div>

			<hr class="mpmls-section-spacer" />
			<h2>Debug Subscriber</h2>
			<p class="description">Enter an email and optional membership ID to see the exact fields we send to MailerLite.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mpmls-debug-email">Email</label></th>
					<td>
						<input type="email" id="mpmls-debug-email" class="regular-text" placeholder="user@example.com" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mpmls-debug-status">Status</label></th>
					<td>
						<select id="mpmls-debug-status">
							<option value="active">Active</option>
							<option value="inactive">Inactive (expired/cancelled)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mpmls-debug-membership">Membership (optional)</label></th>
					<td>
						<?php if ( ! empty( $products ) ) : ?>
							<select id="mpmls-debug-membership">
								<option value="">Auto-detect</option>
								<?php echo $this->render_product_options( $products, '' ); ?>
							</select>
						<?php else : ?>
							<input type="number" id="mpmls-debug-membership" class="regular-text" placeholder="Membership ID (optional)" />
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<button type="button" class="button" id="mpmls-debug-run" data-nonce="<?php echo esc_attr( $nonce ); ?>">Debug subscriber</button>
					</td>
				</tr>
			</table>
			<pre id="mpmls-debug-result" style="background:#fff;border:1px solid #ccd0d4;padding:10px;max-height:240px;overflow:auto;"></pre>

			<hr class="mpmls-section-spacer" />
			<h2>MemberPress Counts</h2>
			<p class="description">Memberships count unique user + membership pairs. Users count unique subscribers. MailerLite groups always show unique subscribers.</p>
			<div class="mpmls-table-wrap">
				<table class="widefat striped">
				<thead>
					<tr>
						<th>Status</th>
						<th>Memberships</th>
						<th>Users</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Active</td>
						<td><?php echo esc_html( (string) $active_counts['memberships'] ); ?></td>
						<td><?php echo esc_html( (string) $active_counts['users'] ); ?></td>
					</tr>
					<tr>
						<td>Inactive</td>
						<td><?php echo esc_html( (string) $inactive_counts['memberships'] ); ?></td>
						<td><?php echo esc_html( (string) $inactive_counts['users'] ); ?></td>
					</tr>
				</tbody>
				</table>
			</div>

			<hr class="mpmls-section-spacer" />
			<h2>Active By Membership</h2>
			<p class="description">Counts of active users per MemberPress product.</p>
			<div class="mpmls-table-wrap">
				<table class="widefat striped">
				<thead>
					<tr>
						<th>Membership</th>
						<th>Product ID</th>
						<th>Users</th>
						<th>Memberships</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $active_breakdown ) ) : ?>
						<tr><td colspan="4">No active membership rows found.</td></tr>
					<?php else : ?>
						<?php foreach ( $active_breakdown as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['title'] ); ?></td>
								<td><?php echo esc_html( (string) $row['product_id'] ); ?></td>
								<td><?php echo esc_html( (string) $row['users'] ); ?></td>
								<td><?php echo esc_html( (string) $row['memberships'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				</table>
			</div>

			<hr class="mpmls-section-spacer" />
			<h2>Diagnostics</h2>
			<p class="description">Use this to verify MemberPress table availability and statuses. Share this section if counts look wrong.</p>
			<div class="mpmls-table-wrap">
				<table class="widefat striped">
				<thead>
					<tr>
						<th>Check</th>
						<th>Result</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>mepr_subscriptions table</td>
						<td><?php echo $diagnostics['subscriptions_table'] ? 'Exists' : 'Missing'; ?></td>
					</tr>
					<tr>
						<td>mepr_transactions table</td>
						<td><?php echo $diagnostics['transactions_table'] ? 'Exists' : 'Missing'; ?></td>
					</tr>
					<tr>
						<td>subscriptions.expires_at column</td>
						<td><?php echo $diagnostics['subscriptions_expires_column'] ? 'Exists' : 'Missing'; ?></td>
					</tr>
					<tr>
						<td>transactions.expires_at column</td>
						<td><?php echo $diagnostics['transactions_expires_column'] ? 'Exists' : 'Missing'; ?></td>
					</tr>
					<tr>
						<td>transactions.subscription_id column</td>
						<td><?php echo $diagnostics['transactions_subscription_column'] ? 'Exists' : 'Missing'; ?></td>
					</tr>
					<tr>
						<td>Active memberships (SQL)</td>
						<td><?php echo esc_html( (string) $diagnostics['active_memberships'] ); ?></td>
					</tr>
					<tr>
						<td>Inactive memberships (SQL)</td>
						<td><?php echo esc_html( (string) $diagnostics['expired_memberships'] ); ?></td>
					</tr>
				</tbody>
				</table>
			</div>
			<div class="mpmls-table-wrap mpmls-section-spacer">
				<table class="widefat striped">
				<thead>
					<tr>
						<th>Subscription statuses</th>
						<th>Count</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $diagnostics['subscription_statuses'] ) ) : ?>
						<tr><td colspan="2">No subscription status rows found.</td></tr>
					<?php else : ?>
						<?php foreach ( $diagnostics['subscription_statuses'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $row['status'] ); ?></td>
								<td><?php echo esc_html( (string) $row['count'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				</table>
			</div>
			<div class="mpmls-table-wrap mpmls-section-spacer">
				<table class="widefat striped">
				<thead>
					<tr>
						<th>Transaction statuses</th>
						<th>Count</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $diagnostics['transaction_statuses'] ) ) : ?>
						<tr><td colspan="2">No transaction status rows found.</td></tr>
					<?php else : ?>
						<?php foreach ( $diagnostics['transaction_statuses'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $row['status'] ); ?></td>
								<td><?php echo esc_html( (string) $row['count'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				</table>
			</div>

			<hr class="mpmls-section-spacer" />
			<h2>Logs</h2>
			<form method="get" class="mpmls-logs-actions">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SYNC_PAGE_SLUG ); ?>" />
				<label for="mpmls_event">Filter by event:</label>
				<select name="mpmls_event" id="mpmls_event">
					<option value="">All</option>
					<?php foreach ( $events as $event ) : ?>
						<option value="<?php echo esc_attr( $event ); ?>" <?php selected( $event_filter, $event ); ?>><?php echo esc_html( $event ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button">Filter</button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mpmls-logs-actions">
				<input type="hidden" name="action" value="mpmls_clear_logs" />
				<?php wp_nonce_field( 'mpmls_clear_logs' ); ?>
				<button class="button">Clear logs</button>
			</form>

			<div class="mpmls-table-wrap">
				<table class="widefat striped">
				<thead>
					<tr>
						<th>Time</th>
						<th>Event</th>
						<th>Email</th>
						<th>User ID</th>
						<th>Membership ID</th>
						<th>Group ID</th>
						<th>Action</th>
						<th>Success</th>
						<th>Message</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="9">No logs found.</td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['created_at'] ); ?></td>
								<td><?php echo esc_html( $log['event'] ); ?></td>
								<td><?php echo esc_html( $log['email'] ); ?></td>
								<td><?php echo esc_html( $log['wp_user_id'] ); ?></td>
								<td><?php echo esc_html( $log['membership_id'] ); ?></td>
								<td><?php echo esc_html( $log['group_id'] ); ?></td>
								<td><?php echo esc_html( $log['action'] ); ?></td>
								<td><?php echo $log['success'] ? 'Yes' : 'No'; ?></td>
								<td><?php echo esc_html( $log['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				</table>
			</div>
		</div>

		<script>
		jQuery(function($){
			var rowIndex = <?php echo (int) count( $rows ); ?>;
			var hasProducts = <?php echo ! empty( $products ) ? 'true' : 'false'; ?>;
			var hasGroups = <?php echo ! empty( $groups ) ? 'true' : 'false'; ?>;
			var productOptions = <?php echo wp_json_encode( $product_options ); ?>;
			var groupOptions = <?php echo wp_json_encode( $group_options ); ?>;
			var syncNonce = <?php echo wp_json_encode( $nonce ); ?>;

			function collectSettings() {
				var settings = {};
				settings['expired_group_id'] = $('#mpmls-expired-group').val() || '';
				settings['logging_enabled'] = $('input[name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[logging_enabled]"]').is(':checked') ? '1' : '';
				var mapping = {};
				$('#mpmls-mapping-table tbody tr').each(function(i){
					var mid = $(this).find('select, input').eq(0).val();
					var gid = $(this).find('select, input').eq(1).val();
					if (mid && gid) {
						mapping[i] = { membership_id: mid, group_id: gid };
					}
				});
				settings['mapping'] = mapping;
				return settings;
			}

			var saveTimer = null;
			var $status = $('#mpmls-save-status');
			function autosave() {
				clearTimeout(saveTimer);
				saveTimer = setTimeout(function(){
					$status.text('Saving...');
					$.post(ajaxurl, {
						action: 'mpmls_autosave_sync',
						nonce: syncNonce,
						settings: collectSettings()
					}, function(response){
						if (response.success) {
							$status.text('Saved.');
							setTimeout(function(){ if ($status.text() === 'Saved.') $status.text(''); }, 2000);
						} else {
							$status.text('Save failed.');
						}
					}).fail(function(){
						$status.text('Save failed.');
					});
				}, 300);
			}

			$(document).on('change', '#mpmls-mapping-table select, #mpmls-mapping-table input, #mpmls-expired-group, input[name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[logging_enabled]"]', autosave);

			$('#mpmls-add-row').on('click', function(){
				var membershipField = hasProducts
					? '<select name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[mapping][' + rowIndex + '][membership_id]">' + productOptions + '</select>'
					: '<input type="number" name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[mapping][' + rowIndex + '][membership_id]" />';
				var groupField = hasGroups
					? '<select name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[mapping][' + rowIndex + '][group_id]">' + groupOptions + '</select>'
					: '<input type="text" name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[mapping][' + rowIndex + '][group_id]" />';
				var row = '<tr>' +
					'<td>' + membershipField + '</td>' +
					'<td>' + groupField + '</td>' +
					'<td><button type="button" class="button mpmls-remove-row">Remove</button></td>' +
				'</tr>';
				$('#mpmls-mapping-table tbody').append(row);
				rowIndex++;
			});

			$('#mpmls-mapping-table').on('click', '.mpmls-remove-row', function(){
				$(this).closest('tr').remove();
				autosave();
			});

			var $progress   = $('#mpmls-progress');
			var $bar        = $('#mpmls-progress-bar');
			var $label      = $('#mpmls-progress-label');
			var $pct        = $('#mpmls-progress-pct');
			var $result     = $('#mpmls-sync-result');
			var $note       = $('#mpmls-sync-note');
			var $dot        = $('.mpmls-sync-head .mpmls-dot');
			var syncRunning = false;

			$(window).on('beforeunload', function(){
				if (syncRunning) {
					return 'A sync is still running. If you leave now it will stop before finishing.';
				}
			});

			function setBar(fraction) {
				var pct = Math.max(0, Math.min(100, Math.round(fraction * 100)));
				$bar.css('width', pct + '%');
				$pct.text(pct + '%');
			}

			function setStep(current) {
				$('.mpmls-step').each(function(){
					var step = parseInt($(this).data('step'), 10);
					$(this).toggleClass('is-active', step === current)
					       .toggleClass('is-done', step < current);
				});
			}

			function chip(cls, label, value) {
				var zero = (cls === 'is-errors' && !value) ? ' is-zero' : '';
				return '<span class="mpmls-chip ' + cls + zero + '">' + label + ': <strong>' + value + '</strong></span>';
			}

			$('#mpmls-sync-now').on('click', function(){
				var $btn = $(this);
				var nonce = $btn.data('nonce');
				var totals = { activeSynced: 0, inactiveSynced: 0, skipped: 0, errors: 0 };

				if (!confirm('Run a full sync now? Active members are placed into their plan groups, churned members into the inactive group.')) {
					return;
				}

				syncRunning = true;
				$btn.prop('disabled', true).text('Syncing\u2026');
				$dot.attr('class', 'mpmls-dot is-running');
				$note.addClass('is-live').html('<strong>Sync in progress \u2014 please keep this tab open.</strong> Closing it early only stops the run; nothing breaks and you can start it again.');
				$result.empty();
				$progress.show();
				setStep(1);
				setBar(0);
				$label.text('Starting\u2026');

				function stop() {
					syncRunning = false;
					$btn.prop('disabled', false).text('Sync now');
					$note.removeClass('is-live').html('Everything syncs automatically on membership changes and every night at 03:30 \u2014 that runs on the server, so no browser window needs to stay open. \u201cSync now\u201d instead runs in this tab: <strong>keep it open until it finishes.</strong> Closing it early only stops the run \u2014 nothing breaks, and you can safely start it again.');
				}

				function fail(message) {
					$dot.attr('class', 'mpmls-dot is-warn');
					$label.text('Stopped');
					$result.html('<span class="mpmls-chip is-message">' + message + '</span>');
					stop();
				}

				function finish(suffix) {
					setStep(3);
					setBar(1);
					$label.text('Finished');
					$dot.attr('class', 'mpmls-dot is-' + (totals.errors ? 'warn' : 'ok'));
					$result.html(
						chip('is-synced', 'Active synced', totals.activeSynced) +
						chip('is-synced', 'Inactive synced', totals.inactiveSynced) +
						chip('is-skipped', 'Skipped', totals.skipped) +
						chip('is-errors', 'Errors', totals.errors) +
						(suffix ? '<span class="mpmls-chip is-skipped">' + suffix + '</span>' : '')
					);
					stop();
				}

				// Each phase fills half of the bar.
				function advance(phase, d) {
					var fraction = d.total ? (d.processed / d.total) : 1;
					setBar(phase === 1 ? fraction * 0.5 : 0.5 + fraction * 0.5);
					$label.text('Step ' + phase + '/2 \u2014 ' + (phase === 1 ? 'active' : 'churned') +
						' members: ' + d.processed + ' / ' + d.total);
				}

				function activeBatch(offset) {
					$.post(ajaxurl, { action: 'mpmls_reconcile_active_members', nonce: nonce, offset: offset }, function(response){
						if (!response.success) { fail(response.data.message); return; }
						var d = response.data;
						totals.activeSynced += d.synced;
						totals.skipped += d.skipped;
						totals.errors += d.errors;
						advance(1, d);
						if (!d.done) { activeBatch(d.offset); } else { setStep(2); inactiveBatch(0); }
					}).fail(function(){ fail('Request failed. Check server logs.'); });
				}

				function inactiveBatch(offset) {
					$.post(ajaxurl, { action: 'mpmls_sync_expired_members', nonce: nonce, offset: offset }, function(response){
						if (!response.success) {
							// No inactive group configured is not fatal - step 2 is simply skipped.
							finish('Step 2 skipped: ' + response.data.message);
							return;
						}
						var d = response.data;
						totals.inactiveSynced += d.synced;
						totals.skipped += d.skipped;
						totals.errors += d.errors;
						advance(2, d);
						if (!d.done) { inactiveBatch(d.offset); } else { finish(); }
					}).fail(function(){ fail('Request failed. Check server logs.'); });
				}

				activeBatch(0);
			});

			$('#mpmls-debug-run').on('click', function(){
				var email = $.trim($('#mpmls-debug-email').val());
				var status = $('#mpmls-debug-status').val();
				var membershipId = $('#mpmls-debug-membership').val() || '';
				var $out = $('#mpmls-debug-result');
				if (!email) {
					$out.text('Please enter an email address.');
					return;
				}
				$out.text('Loading...');
				$.post(ajaxurl, {
					action: 'mpmls_debug_subscriber',
					nonce: $(this).data('nonce'),
					email: email,
					status: status,
					membership_id: membershipId
				}, function(response){
					if (response.success) {
						$out.text(JSON.stringify(response.data, null, 2));
					} else {
						$out.text('Error: ' + response.data.message);
					}
				}).fail(function(){
					$out.text('Request failed.');
				});
			});
		});
		</script>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/*  Save / AJAX handlers                                              */
	/* ------------------------------------------------------------------ */

	public function sanitize_settings( $input ) {
		$output = array();

		$output['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';

		$output['expired_group_id'] = $this->normalize_group_id(
			isset( $input['expired_group_id'] ) ? sanitize_text_field( $input['expired_group_id'] ) : ''
		);
		$output['logging_enabled']  = ! empty( $input['logging_enabled'] ) ? 1 : 0;

		$mapping = array();
		if ( ! empty( $input['mapping'] ) && is_array( $input['mapping'] ) ) {
			foreach ( $input['mapping'] as $row ) {
				$membership_id = isset( $row['membership_id'] ) ? absint( $row['membership_id'] ) : 0;
				$group_id      = $this->normalize_group_id(
					isset( $row['group_id'] ) ? sanitize_text_field( $row['group_id'] ) : ''
				);
				if ( $membership_id && $group_id !== '' ) {
					$mapping[ $membership_id ] = $group_id;
				}
			}
		}
		$output['mapping'] = $mapping;

		return $output;
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$client   = new MPMLS_MailerLite_Client( $api_key );
		$response = $client->test_connection();
		if ( is_wp_error( $response ) ) {
			update_option( 'mpmls_connection_status', array( 'ok' => false, 'key_hash' => md5( $api_key ) ) );
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$settings            = get_option( MPMLS_OPTION_KEY, array() );
		$settings['api_key'] = $api_key;
		update_option( MPMLS_OPTION_KEY, $settings );

		update_option( 'mpmls_connection_status', array( 'ok' => true, 'key_hash' => md5( $api_key ), 'time' => current_time( 'mysql' ) ) );
		wp_send_json_success( array( 'message' => 'Connection OK.' ) );
	}

	public function ajax_disconnect_api() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$settings['api_key'] = '';
		update_option( MPMLS_OPTION_KEY, $settings );
		delete_option( 'mpmls_connection_status' );

		wp_send_json_success( array( 'message' => 'Disconnected.' ) );
	}

	public function ajax_autosave_sync() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? wp_unslash( $_POST['settings'] )
			: array();

		$settings            = get_option( MPMLS_OPTION_KEY, array() );
		$sanitized           = $this->sanitize_settings( $input );
		$sanitized['api_key'] = isset( $settings['api_key'] ) ? $settings['api_key'] : '';

		update_option( MPMLS_OPTION_KEY, $sanitized );

		wp_send_json_success( array( 'message' => 'Saved.' ) );
	}

	public function ajax_reconcile_active_members() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$result = MPMLS_Sync_Engine::instance()->run_active_batch( $offset, 10, 'bulk_reconcile' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	public function ajax_sync_expired_members() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$result = MPMLS_Sync_Engine::instance()->run_inactive_batch( $offset, 5, 'bulk_expired_sync' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	public function ajax_debug_subscriber() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active';
		$membership_id = isset( $_POST['membership_id'] ) ? absint( $_POST['membership_id'] ) : 0;

		if ( $email === '' ) {
			wp_send_json_error( array( 'message' => 'Email is required.' ) );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => 'No WordPress user found for that email.' ) );
		}

		$is_inactive = $status === 'inactive';
		$rows = $this->get_memberships_for_user( (int) $user->ID, $is_inactive );

		if ( empty( $rows ) ) {
			wp_send_json_error( array( 'message' => 'No matching membership rows found for this user and status.' ) );
		}

		if ( ! $membership_id ) {
			if ( count( $rows ) > 1 ) {
				wp_send_json_success( array(
					'message'     => 'Multiple memberships found. Provide a membership ID to debug a specific one.',
					'memberships' => $rows,
				) );
			}
			$membership_id = (int) $rows[0]['product_id'];
		}

		$selected = null;
		foreach ( $rows as $row ) {
			if ( (int) $row['product_id'] === $membership_id ) {
				$selected = $row;
				break;
			}
		}
		if ( ! $selected ) {
			wp_send_json_error( array( 'message' => 'Membership ID not found for this user in the selected status.' ) );
		}

		$expires_at = isset( $selected['expires_at'] ) ? (string) $selected['expires_at'] : '';
		$status_value = $is_inactive ? 'expired' : 'active';
		$fields = $this->build_subscriber_fields( $user, $membership_id, $expires_at, $status_value );

		$mapping = mpmls_get_setting( 'mapping', array() );
		$group_id = $is_inactive
			? mpmls_get_setting( 'expired_group_id', '' )
			: ( isset( $mapping[ $membership_id ] ) ? (string) $mapping[ $membership_id ] : '' );

		wp_send_json_success( array(
			'email'          => $email,
			'user_id'        => (int) $user->ID,
			'membership_id'  => (int) $membership_id,
			'membership'     => get_the_title( $membership_id ) ?: '',
			'status'         => $status_value,
			'expires_at_raw' => $expires_at,
			'group_id'       => $group_id,
			'fields'         => $fields,
			'note'           => $group_id === '' ? 'No mapped group for this membership/status.' : '',
		) );
	}

	public function handle_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( 'mpmls_clear_logs' );

		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . MPMLS_Logger::table_name() );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SYNC_PAGE_SLUG ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Helpers                                                           */
	/* ------------------------------------------------------------------ */

	protected function get_active_members() {
		return MPMLS_Sync_Engine::instance()->get_active_members();
	}

	protected function get_active_members_sql( $user_id = 0, $with_order = true ) {
		return MPMLS_Sync_Engine::instance()->get_active_members_sql( $user_id, $with_order );
	}

	protected function get_mapping() {
		return MPMLS_Sync_Engine::instance()->get_mapping();
	}

	protected function get_active_membership_ids_for_user( $user_id ) {
		return MPMLS_Sync_Engine::instance()->get_active_membership_ids_for_user( $user_id );
	}

	protected function user_has_paid_transaction( $user_id ) {
		return MPMLS_Sync_Engine::instance()->user_has_paid_transaction( $user_id );
	}

	protected function get_active_group_ids_for_user( $user_id ) {
		return MPMLS_Sync_Engine::instance()->get_active_group_ids_for_user( $user_id );
	}

	protected function remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $email, $event_name, $include_expired_group = false ) {
		MPMLS_Sync_Engine::instance()->remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $email, $event_name, $include_expired_group );
	}

	protected function get_expired_members() {
		return MPMLS_Sync_Engine::instance()->get_expired_members();
	}

	protected function get_expired_members_sql( $with_order = true, $user_id = 0 ) {
		return MPMLS_Sync_Engine::instance()->get_expired_members_sql( $with_order, $user_id );
	}

	protected function get_member_counts( $sql ) {
		global $wpdb;

		if ( $sql === '' ) {
			return array(
				'memberships' => 0,
				'users'       => 0,
			);
		}

		$count_sql = "SELECT COUNT(*) AS memberships, COUNT(DISTINCT user_id) AS users FROM ( {$sql} ) mpmls_counts";
		$counts = $wpdb->get_row( $count_sql, ARRAY_A );

		return array(
			'memberships' => isset( $counts['memberships'] ) ? (int) $counts['memberships'] : 0,
			'users'       => isset( $counts['users'] ) ? (int) $counts['users'] : 0,
		);
	}

	protected function get_memberships_for_user( $user_id, $inactive = false ) {
		global $wpdb;

		if ( ! $user_id ) {
			return array();
		}

		if ( $inactive ) {
			$sql = $this->get_expired_members_sql( true, (int) $user_id );
		} else {
			$sql = $this->get_active_members_sql( (int) $user_id, true );
		}

		if ( $sql === '' ) {
			return array();
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	protected function get_membership_breakdown( $sql ) {
		global $wpdb;

		if ( $sql === '' ) {
			return array();
		}

		$rows = $wpdb->get_results(
			"SELECT product_id, COUNT(*) AS memberships, COUNT(DISTINCT user_id) AS users
				FROM ( {$sql} ) mpmls_rows
				GROUP BY product_id
				ORDER BY users DESC",
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		$results = array();
		foreach ( $rows as $row ) {
			$product_id = (int) $row['product_id'];
			$title = $product_id ? (string) get_the_title( $product_id ) : '';
			if ( $title === '' ) {
				$title = $product_id ? 'Unknown (#' . $product_id . ')' : 'Unknown';
			}
			$results[] = array(
				'product_id'  => $product_id,
				'title'       => $title,
				'users'       => isset( $row['users'] ) ? (int) $row['users'] : 0,
				'memberships' => isset( $row['memberships'] ) ? (int) $row['memberships'] : 0,
			);
		}

		return $results;
	}

	protected function get_diagnostics() {
		global $wpdb;

		$subscriptions_table = $wpdb->prefix . 'mepr_subscriptions';
		$transactions_table  = $wpdb->prefix . 'mepr_transactions';

		$subscriptions_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subscriptions_table ) ) === $subscriptions_table;
		$transactions_exists  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $transactions_table ) ) === $transactions_table;

		$subscriptions_expires = false;
		$transactions_expires  = false;
		$transactions_subscription = false;

		if ( $subscriptions_exists ) {
			$subscriptions_expires = (bool) $wpdb->get_var( $wpdb->prepare(
				"SHOW COLUMNS FROM {$subscriptions_table} LIKE %s",
				'expires_at'
			) );
		}

		if ( $transactions_exists ) {
			$transactions_expires = (bool) $wpdb->get_var( $wpdb->prepare(
				"SHOW COLUMNS FROM {$transactions_table} LIKE %s",
				'expires_at'
			) );
			$transactions_subscription = (bool) $wpdb->get_var( $wpdb->prepare(
				"SHOW COLUMNS FROM {$transactions_table} LIKE %s",
				'subscription_id'
			) );
		}

		$subscription_statuses = array();
		if ( $subscriptions_exists ) {
			$subscription_statuses = $wpdb->get_results(
				"SELECT status, COUNT(*) AS count
					FROM {$subscriptions_table}
					GROUP BY status
					ORDER BY count DESC",
				ARRAY_A
			);
		}

		$transaction_statuses = array();
		if ( $transactions_exists ) {
			$transaction_statuses = $wpdb->get_results(
				"SELECT status, COUNT(*) AS count
					FROM {$transactions_table}
					GROUP BY status
					ORDER BY count DESC",
				ARRAY_A
			);
		}

		$active_counts   = $this->get_member_counts( $this->get_active_members_sql( 0, false ) );
		$expired_counts  = $this->get_member_counts( $this->get_expired_members_sql( false ) );

		return array(
			'subscriptions_table'            => $subscriptions_exists,
			'transactions_table'             => $transactions_exists,
			'subscriptions_expires_column'   => $subscriptions_expires,
			'transactions_expires_column'    => $transactions_expires,
			'transactions_subscription_column' => $transactions_subscription,
			'active_memberships'             => $active_counts['memberships'],
			'expired_memberships'            => $expired_counts['memberships'],
			'subscription_statuses'          => $subscription_statuses,
			'transaction_statuses'           => $transaction_statuses,
		);
	}

	protected function build_subscriber_fields( $user, $product_id, $expires_at, $status ) {
		return MPMLS_Sync_Engine::instance()->build_subscriber_fields( $user, $product_id, $expires_at, $status );
	}

	protected function get_auto_sync_status_state() {
		if ( ! class_exists( 'MPMLS_Auto_Sync' ) ) {
			return 'off';
		}

		if ( ! MPMLS_Auto_Sync::is_enabled() ) {
			return 'off';
		}

		if ( MPMLS_Auto_Sync::is_running() ) {
			return 'running';
		}

		$last = MPMLS_Auto_Sync::get_last_run();
		if ( empty( $last['finished'] ) ) {
			return 'idle';
		}

		if ( ! empty( $last['error'] ) || ! empty( $last['totals']['errors'] ) ) {
			return 'warn';
		}

		return 'ok';
	}

	protected function get_auto_sync_status_text() {
		if ( ! class_exists( 'MPMLS_Auto_Sync' ) ) {
			return '';
		}

		if ( ! MPMLS_Auto_Sync::is_enabled() ) {
			return 'Automatic nightly sync is disabled in this environment.';
		}

		if ( MPMLS_Auto_Sync::is_running() ) {
			return 'Automatic nightly sync is running right now.';
		}

		$last = MPMLS_Auto_Sync::get_last_run();
		if ( empty( $last['finished'] ) ) {
			return 'Automatic nightly sync is scheduled for 03:30 every night. No completed runs yet.';
		}

		$totals = wp_parse_args( isset( $last['totals'] ) ? $last['totals'] : array(), array(
			'active_synced'    => 0,
			'active_skipped'   => 0,
			'inactive_synced'  => 0,
			'inactive_skipped' => 0,
			'errors'           => 0,
		) );

		$text = sprintf(
			'Automatic nightly sync — last run %s: %d active synced, %d inactive synced, %d skipped, %d errors.',
			wp_date( 'j.n.Y H:i', (int) $last['finished'] ),
			$totals['active_synced'],
			$totals['inactive_synced'],
			$totals['active_skipped'] + $totals['inactive_skipped'],
			$totals['errors']
		);

		if ( ! empty( $last['error'] ) ) {
			$text .= ' Aborted: ' . $last['error'];
		}

		return $text;
	}

	protected function get_logs() {
		global $wpdb;
		$table        = MPMLS_Logger::table_name();
		$event_filter = isset( $_GET['mpmls_event'] ) ? sanitize_text_field( wp_unslash( $_GET['mpmls_event'] ) ) : '';

		$sql    = "SELECT * FROM {$table}";
		$params = array();
		if ( $event_filter !== '' ) {
			$sql     .= ' WHERE event = %s';
			$params[] = $event_filter;
		}
		$sql .= ' ORDER BY id DESC LIMIT 200';

		if ( ! empty( $params ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	protected function get_memberpress_products() {
		if ( ! post_type_exists( 'memberpressproduct' ) ) {
			return array();
		}
		$products = get_posts(
			array(
				'post_type'      => 'memberpressproduct',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$result = array();
		foreach ( $products as $product ) {
			$result[] = array(
				'id'    => (int) $product->ID,
				'title' => $product->post_title,
			);
		}
		return $result;
	}

	protected function get_mailerlite_groups( $api_key ) {
		if ( empty( $api_key ) ) {
			return array();
		}
		$cache_key = 'mpmls_groups_' . md5( $api_key );
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}
		$client = new MPMLS_MailerLite_Client( $api_key );
		$groups = $client->list_groups( 200 );
		if ( is_wp_error( $groups ) ) {
			return $groups;
		}
		set_transient( $cache_key, $groups, 5 * MINUTE_IN_SECONDS );
		return $groups;
	}

	protected function render_product_options( $products, $selected ) {
		$selected = (string) $selected;
		$options  = '<option value="">Select product</option>';
		$found    = false;
		foreach ( $products as $product ) {
			$value       = (string) $product['id'];
			$is_selected = selected( $selected, $value, false );
			if ( $is_selected ) {
				$found = true;
			}
			$options .= '<option value="' . esc_attr( $value ) . '" ' . $is_selected . '>' . esc_html( $product['title'] . ' (#' . $value . ')' ) . '</option>';
		}
		if ( $selected !== '' && ! $found ) {
			$options = '<option value="' . esc_attr( $selected ) . '" selected>Unknown product (#' . esc_html( $selected ) . ')</option>' . $options;
		}
		return $options;
	}

	protected function render_group_options( $groups, $selected, $allow_empty = false ) {
		$selected = (string) $selected;
		$options  = $allow_empty ? '<option value="">No group</option>' : '<option value="">Select group</option>';
		$found    = false;
		foreach ( $groups as $group ) {
			$value       = (string) $group['id'];
			$is_selected = selected( $selected, $value, false );
			if ( $is_selected ) {
				$found = true;
			}
			$options .= '<option value="' . esc_attr( $value ) . '" ' . $is_selected . '>' . esc_html( $group['name'] . ' (#' . $value . ')' ) . '</option>';
		}
		if ( $selected !== '' && ! $found ) {
			$options = '<option value="' . esc_attr( $selected ) . '" selected>Unknown group (#' . esc_html( $selected ) . ')</option>' . $options;
		}
		return $options;
	}

	protected function normalize_group_id( $value ) {
		$value = trim( (string) $value );
		$value = stripslashes( $value );
		$value = trim( $value, '"\'' );
		if ( $value === '' ) {
			return '';
		}
		return $value;
	}

	protected function get_log_events() {
		global $wpdb;
		$table = MPMLS_Logger::table_name();
		return $wpdb->get_col( "SELECT DISTINCT event FROM {$table} ORDER BY event ASC" );
	}
}
