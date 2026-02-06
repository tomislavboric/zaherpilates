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
		add_action( 'wp_ajax_mpmls_send_test_event', array( $this, 'ajax_send_test_event' ) );
		add_action( 'wp_ajax_mpmls_sync_all_members', array( $this, 'ajax_sync_all_members' ) );
		add_action( 'wp_ajax_mpmls_reconcile_active_members', array( $this, 'ajax_reconcile_active_members' ) );
		add_action( 'wp_ajax_mpmls_sync_expired_members', array( $this, 'ajax_sync_expired_members' ) );
		add_action( 'wp_ajax_mpmls_sync_cancelled_members', array( $this, 'ajax_sync_cancelled_members' ) );
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
		$cancelled_group_id = isset( $settings['cancelled_group_id'] ) ? $this->normalize_group_id( $settings['cancelled_group_id'] ) : '';
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
		$expired_counts   = $this->get_member_counts( $this->get_expired_members_sql( false ) );
		$cancelled_counts = $this->get_member_counts( $this->get_cancelled_members_sql( false ) );
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
							<p><button type="button" class="button" id="mpmls-add-row">Add mapping</button></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mpmls-cancelled-group">Cancelled group ID</label></th>
						<td>
							<?php if ( ! empty( $groups ) ) : ?>
								<select id="mpmls-cancelled-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[cancelled_group_id]" class="regular-text">
									<?php echo $this->render_group_options( $groups, $cancelled_group_id, true ); ?>
								</select>
							<?php else : ?>
								<input type="text" id="mpmls-cancelled-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[cancelled_group_id]" value="<?php echo esc_attr( $cancelled_group_id ); ?>" class="regular-text" />
							<?php endif; ?>
							<p class="description">Optional group ID for users who cancel (subscription stopped).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mpmls-expired-group">Expired group ID</label></th>
						<td>
							<?php if ( ! empty( $groups ) ) : ?>
								<select id="mpmls-expired-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[expired_group_id]" class="regular-text">
									<?php echo $this->render_group_options( $groups, $expired_group_id, true ); ?>
								</select>
							<?php else : ?>
								<input type="text" id="mpmls-expired-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[expired_group_id]" value="<?php echo esc_attr( $expired_group_id ); ?>" class="regular-text" />
							<?php endif; ?>
							<p class="description">Optional group ID for users whose subscription expires.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Logging</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[logging_enabled]" value="1" <?php checked( $logging_enabled ); ?> /> Enable logging</label>
						</td>
					</tr>
				</table>
				<div class="mpmls-quick-actions">
					<button type="button" class="button" id="mpmls-test-event" data-nonce="<?php echo esc_attr( $nonce ); ?>">Send test event</button>
					<button type="button" class="button" id="mpmls-sync-all" data-nonce="<?php echo esc_attr( $nonce ); ?>">Sync all members</button>
					<button type="button" class="button" id="mpmls-reconcile" data-nonce="<?php echo esc_attr( $nonce ); ?>">Reconcile active members</button>
					<button type="button" class="button" id="mpmls-sync-expired" data-nonce="<?php echo esc_attr( $nonce ); ?>">Sync expired members</button>
					<button type="button" class="button" id="mpmls-sync-cancelled" data-nonce="<?php echo esc_attr( $nonce ); ?>">Sync cancelled members</button>
					<span id="mpmls-sync-result"></span>
				</div>

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
						<td>Expired</td>
						<td><?php echo esc_html( (string) $expired_counts['memberships'] ); ?></td>
						<td><?php echo esc_html( (string) $expired_counts['users'] ); ?></td>
					</tr>
					<tr>
						<td>Cancelled</td>
						<td><?php echo esc_html( (string) $cancelled_counts['memberships'] ); ?></td>
						<td><?php echo esc_html( (string) $cancelled_counts['users'] ); ?></td>
					</tr>
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
						<td>Expired memberships (SQL)</td>
						<td><?php echo esc_html( (string) $diagnostics['expired_memberships'] ); ?></td>
					</tr>
					<tr>
						<td>Cancelled memberships (SQL)</td>
						<td><?php echo esc_html( (string) $diagnostics['cancelled_memberships'] ); ?></td>
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
				settings['cancelled_group_id'] = $('#mpmls-cancelled-group').val() || '';
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
			var $status = $('#mpmls-sync-result');
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

			$(document).on('change', '#mpmls-mapping-table select, #mpmls-mapping-table input, #mpmls-cancelled-group, #mpmls-expired-group, input[name="<?php echo esc_js( MPMLS_OPTION_KEY ); ?>[logging_enabled]"]', autosave);

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

			$('#mpmls-test-event').on('click', function(){
				$status.text('Sending test...');
				$.post(ajaxurl, {
					action: 'mpmls_send_test_event',
					nonce: $(this).data('nonce')
				}, function(response){
					if(response.success){
						$status.text('Success: ' + response.data.message);
					} else {
						$status.text('Error: ' + response.data.message);
					}
				});
			});

			$('#mpmls-sync-all').on('click', function(){
				var $btn = $(this);
				var nonce = $btn.data('nonce');
				var totalSynced = 0, totalSkipped = 0, totalErrors = 0;

				if (!confirm('This will sync all active MemberPress members to their mapped MailerLite groups. Continue?')) {
					return;
				}

				$btn.prop('disabled', true);
				$status.text('Starting sync...');

				function syncBatch(offset) {
					$.post(ajaxurl, {
						action: 'mpmls_sync_all_members',
						nonce: nonce,
						offset: offset
					}, function(response){
						if (!response.success) {
							$status.text('Error: ' + response.data.message);
							$btn.prop('disabled', false);
							return;
						}
						var d = response.data;
						totalSynced += d.synced;
						totalSkipped += d.skipped;
						totalErrors += d.errors;
						$status.text('Syncing... ' + d.processed + '/' + d.total + ' members');

						if (!d.done) {
							syncBatch(d.offset);
						} else {
							$status.text('Done! Synced: ' + totalSynced + ', Skipped: ' + totalSkipped + ', Errors: ' + totalErrors);
							$btn.prop('disabled', false);
						}
					}).fail(function(){
						$status.text('Request failed. Check server logs.');
						$btn.prop('disabled', false);
					});
				}

				syncBatch(0);
			});

			$('#mpmls-reconcile').on('click', function(){
				var $btn = $(this);
				var nonce = $btn.data('nonce');
				var totalSynced = 0, totalSkipped = 0, totalErrors = 0;

				if (!confirm('This will reconcile active MemberPress members with their mapped MailerLite groups. Continue?')) {
					return;
				}

				$btn.prop('disabled', true);
				$status.text('Starting reconcile...');

				function syncBatch(offset) {
					$.post(ajaxurl, {
						action: 'mpmls_reconcile_active_members',
						nonce: nonce,
						offset: offset
					}, function(response){
						if (!response.success) {
							$status.text('Error: ' + response.data.message);
							$btn.prop('disabled', false);
							return;
						}
						var d = response.data;
						totalSynced += d.synced;
						totalSkipped += d.skipped;
						totalErrors += d.errors;
						$status.text('Reconciling... ' + d.processed + '/' + d.total + ' members');

						if (!d.done) {
							syncBatch(d.offset);
						} else {
							$status.text('Done! Reconciled: ' + totalSynced + ', Skipped: ' + totalSkipped + ', Errors: ' + totalErrors);
							$btn.prop('disabled', false);
						}
					}).fail(function(){
						$status.text('Request failed. Check server logs.');
						$btn.prop('disabled', false);
					});
				}

				syncBatch(0);
			});

			$('#mpmls-sync-expired').on('click', function(){
				var $btn = $(this);
				var nonce = $btn.data('nonce');
				var totalSynced = 0, totalSkipped = 0, totalErrors = 0;

				if (!confirm('This will sync expired members to the Expired group. Continue?')) {
					return;
				}

				$btn.prop('disabled', true);
				$status.text('Starting expired sync...');

				function syncBatch(offset) {
					$.post(ajaxurl, {
						action: 'mpmls_sync_expired_members',
						nonce: nonce,
						offset: offset
					}, function(response){
						if (!response.success) {
							$status.text('Error: ' + response.data.message);
							$btn.prop('disabled', false);
							return;
						}
						var d = response.data;
						totalSynced += d.synced;
						totalSkipped += d.skipped;
						totalErrors += d.errors;
						$status.text('Syncing expired... ' + d.processed + '/' + d.total + ' members');

						if (!d.done) {
							syncBatch(d.offset);
						} else {
							$status.text('Done! Expired synced: ' + totalSynced + ', Skipped: ' + totalSkipped + ', Errors: ' + totalErrors);
							$btn.prop('disabled', false);
						}
					}).fail(function(){
						$status.text('Request failed. Check server logs.');
						$btn.prop('disabled', false);
					});
				}

				syncBatch(0);
			});

			$('#mpmls-sync-cancelled').on('click', function(){
				var $btn = $(this);
				var nonce = $btn.data('nonce');
				var totalSynced = 0, totalSkipped = 0, totalErrors = 0;

				if (!confirm('This will sync cancelled members to the Cancelled group. Continue?')) {
					return;
				}

				$btn.prop('disabled', true);
				$status.text('Starting cancelled sync...');

				function syncBatch(offset) {
					$.post(ajaxurl, {
						action: 'mpmls_sync_cancelled_members',
						nonce: nonce,
						offset: offset
					}, function(response){
						if (!response.success) {
							$status.text('Error: ' + response.data.message);
							$btn.prop('disabled', false);
							return;
						}
						var d = response.data;
						totalSynced += d.synced;
						totalSkipped += d.skipped;
						totalErrors += d.errors;
						$status.text('Syncing cancelled... ' + d.processed + '/' + d.total + ' members');

						if (!d.done) {
							syncBatch(d.offset);
						} else {
							$status.text('Done! Cancelled synced: ' + totalSynced + ', Skipped: ' + totalSkipped + ', Errors: ' + totalErrors);
							$btn.prop('disabled', false);
						}
					}).fail(function(){
						$status.text('Request failed. Check server logs.');
						$btn.prop('disabled', false);
					});
				}

				syncBatch(0);
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
		$output['cancelled_group_id'] = $this->normalize_group_id(
			isset( $input['cancelled_group_id'] ) ? sanitize_text_field( $input['cancelled_group_id'] ) : ''
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

		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$settings['api_key'] = '';
		update_option( MPMLS_OPTION_KEY, $settings );
		delete_option( 'mpmls_connection_status' );

		wp_send_json_success( array( 'message' => 'Disconnected.' ) );
	}

	public function ajax_autosave_sync() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? wp_unslash( $_POST['settings'] )
			: array();

		$settings            = get_option( MPMLS_OPTION_KEY, array() );
		$sanitized           = $this->sanitize_settings( $input );
		$sanitized['api_key'] = isset( $settings['api_key'] ) ? $settings['api_key'] : '';

		update_option( MPMLS_OPTION_KEY, $sanitized );

		wp_send_json_success( array( 'message' => 'Saved.' ) );
	}

	public function ajax_send_test_event() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$mapping  = isset( $settings['mapping'] ) && is_array( $settings['mapping'] ) ? $settings['mapping'] : array();
		if ( empty( $mapping ) ) {
			wp_send_json_error( array( 'message' => 'No membership - group mapping found.' ) );
		}

		$first_membership_id = (int) array_key_first( $mapping );
		$group_id            = (string) $mapping[ $first_membership_id ];
		if ( ! $first_membership_id || $group_id === '' ) {
			wp_send_json_error( array( 'message' => 'Invalid mapping data.' ) );
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->user_email ) ) {
			wp_send_json_error( array( 'message' => 'No current user email found.' ) );
		}

		$client        = new MPMLS_MailerLite_Client( $api_key );
		$subscriber_id = $client->upsert_subscriber( $user->user_email );
		if ( is_wp_error( $subscriber_id ) ) {
			wp_send_json_error( array( 'message' => $subscriber_id->get_error_message() ) );
		}

		$result = $client->add_to_group( $subscriber_id, $group_id, $user->user_email );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		MPMLS_Logger::log( array(
			'event'         => 'test_event',
			'email'         => $user->user_email,
			'wp_user_id'    => (int) $user->ID,
			'membership_id' => $first_membership_id,
			'group_id'      => $group_id,
			'action'        => 'test',
			'success'       => 1,
			'message'       => 'Test event sent from settings.',
		) );

		wp_send_json_success( array( 'message' => 'Test event sent to group.' ) );
	}

	public function ajax_sync_all_members() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$mapping  = isset( $settings['mapping'] ) && is_array( $settings['mapping'] ) ? $settings['mapping'] : array();
		if ( empty( $mapping ) ) {
			wp_send_json_error( array( 'message' => 'No membership - group mapping found.' ) );
		}

		$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch   = 10;
		$members = $this->get_active_members();
		$total   = count( $members );
		$slice   = array_slice( $members, $offset, $batch );
		$synced  = 0;
		$skipped = 0;
		$errors  = 0;
		$client  = new MPMLS_MailerLite_Client( $api_key );

		foreach ( $slice as $member ) {
			$product_id = (int) $member['product_id'];
			$user_id    = (int) $member['user_id'];
			$group_id   = isset( $mapping[ $product_id ] ) ? (string) $mapping[ $product_id ] : '';

			if ( $group_id === '' ) {
				$skipped++;
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user || empty( $user->user_email ) ) {
				$skipped++;
				continue;
			}

			$expires_at = isset( $member['expires_at'] ) && $member['expires_at'] !== '0000-00-00 00:00:00'
				? (string) $member['expires_at'] : '';

			$fields = array(
				'name'              => $user->first_name ?: '',
				'last_name'         => $user->last_name ?: '',
				'membership_name'   => get_the_title( $product_id ) ?: '',
				'membership_status' => 'active',
			);
			if ( $user->user_registered ) {
				$fields['signup_date'] = date( 'Y-m-d', strtotime( $user->user_registered ) );
			}
			if ( $expires_at !== '' ) {
				$fields['membership_expiry'] = date( 'Y-m-d', strtotime( $expires_at ) );
			}
			$fields = array_filter( $fields, function ( $v ) { return $v !== ''; } );

			$subscriber_id = $client->upsert_subscriber( $user->user_email, $fields );
			if ( is_wp_error( $subscriber_id ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_sync',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $group_id,
					'action'        => 'activate',
					'success'       => 0,
					'message'       => $subscriber_id->get_error_message(),
				) );
				continue;
			}

			$result = $client->add_to_group( $subscriber_id, $group_id, $user->user_email, $fields );
			if ( is_wp_error( $result ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_sync',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $group_id,
					'action'        => 'activate',
					'success'       => 0,
					'message'       => $result->get_error_message(),
				) );
				continue;
			}

			$synced++;
			MPMLS_Logger::log( array(
				'event'         => 'bulk_sync',
				'email'         => $user->user_email,
				'wp_user_id'    => $user_id,
				'membership_id' => $product_id,
				'group_id'      => $group_id,
				'action'        => 'activate',
				'success'       => 1,
				'message'       => 'Bulk sync: added to group.',
			) );
		}

		$new_offset = $offset + $batch;
		$done       = $new_offset >= $total;

		wp_send_json_success( array(
			'processed' => min( $new_offset, $total ),
			'total'     => $total,
			'synced'    => $synced,
			'skipped'   => $skipped,
			'errors'    => $errors,
			'done'      => $done,
			'offset'    => $new_offset,
		) );
	}

	public function ajax_reconcile_active_members() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$mapping  = isset( $settings['mapping'] ) && is_array( $settings['mapping'] ) ? $settings['mapping'] : array();
		if ( empty( $mapping ) ) {
			wp_send_json_error( array( 'message' => 'No membership - group mapping found.' ) );
		}

		$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch   = 10;
		$members = $this->get_active_members();
		$total   = count( $members );
		$slice   = array_slice( $members, $offset, $batch );
		$synced  = 0;
		$skipped = 0;
		$errors  = 0;
		$client  = new MPMLS_MailerLite_Client( $api_key );

		foreach ( $slice as $member ) {
			$product_id = (int) $member['product_id'];
			$user_id    = (int) $member['user_id'];
			$group_id   = isset( $mapping[ $product_id ] ) ? (string) $mapping[ $product_id ] : '';

			if ( $group_id === '' ) {
				$skipped++;
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user || empty( $user->user_email ) ) {
				$skipped++;
				continue;
			}

			$expires_at = isset( $member['expires_at'] ) && $member['expires_at'] !== '0000-00-00 00:00:00'
				? (string) $member['expires_at'] : '';

			$fields = array(
				'name'              => $user->first_name ?: '',
				'last_name'         => $user->last_name ?: '',
				'membership_name'   => get_the_title( $product_id ) ?: '',
				'membership_status' => 'active',
			);
			if ( $user->user_registered ) {
				$fields['signup_date'] = date( 'Y-m-d', strtotime( $user->user_registered ) );
			}
			if ( $expires_at !== '' ) {
				$fields['membership_expiry'] = date( 'Y-m-d', strtotime( $expires_at ) );
			}
			$fields = array_filter( $fields, function ( $v ) { return $v !== ''; } );

			$subscriber_id = $client->upsert_subscriber( $user->user_email, $fields );
			if ( is_wp_error( $subscriber_id ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_reconcile',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $group_id,
					'action'        => 'activate',
					'success'       => 0,
					'message'       => $subscriber_id->get_error_message(),
				) );
				continue;
			}

			$result = $client->add_to_group( $subscriber_id, $group_id, $user->user_email, $fields );
			if ( is_wp_error( $result ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_reconcile',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $group_id,
					'action'        => 'activate',
					'success'       => 0,
					'message'       => $result->get_error_message(),
				) );
				continue;
			}

			$this->remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $user->user_email, 'bulk_reconcile' );

			$synced++;
			MPMLS_Logger::log( array(
				'event'         => 'bulk_reconcile',
				'email'         => $user->user_email,
				'wp_user_id'    => $user_id,
				'membership_id' => $product_id,
				'group_id'      => $group_id,
				'action'        => 'activate',
				'success'       => 1,
				'message'       => 'Reconcile: added to group and cleaned inactive mapped groups.',
			) );
		}

		$new_offset = $offset + $batch;
		$done       = $new_offset >= $total;

		wp_send_json_success( array(
			'processed' => min( $new_offset, $total ),
			'total'     => $total,
			'synced'    => $synced,
			'skipped'   => $skipped,
			'errors'    => $errors,
			'done'      => $done,
			'offset'    => $new_offset,
		) );
	}

	public function ajax_sync_expired_members() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$expired_group_id = mpmls_get_setting( 'expired_group_id', '' );
		if ( $expired_group_id === '' ) {
			wp_send_json_error( array( 'message' => 'Expired group ID is not configured.' ) );
		}

		$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch   = 5;
		$members = $this->get_expired_members();
		$total   = count( $members );
		$slice   = array_slice( $members, $offset, $batch );
		$synced  = 0;
		$skipped = 0;
		$errors  = 0;
		$client  = new MPMLS_MailerLite_Client( $api_key );

		foreach ( $slice as $member ) {
			$product_id = (int) $member['product_id'];
			$user_id    = (int) $member['user_id'];
			$expires_at = isset( $member['expires_at'] ) ? (string) $member['expires_at'] : '';

			$user = get_userdata( $user_id );
			if ( ! $user || empty( $user->user_email ) ) {
				$skipped++;
				continue;
			}

			$fields = $this->build_subscriber_fields( $user, $product_id, $expires_at, 'expired' );

			$subscriber_id = $client->upsert_subscriber( $user->user_email, $fields );
			if ( is_wp_error( $subscriber_id ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_expired_sync',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $expired_group_id,
					'action'        => 'deactivate',
					'success'       => 0,
					'message'       => $subscriber_id->get_error_message(),
				) );
				continue;
			}

			$result = $client->add_to_group( $subscriber_id, $expired_group_id, $user->user_email, $fields );
			if ( is_wp_error( $result ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_expired_sync',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $expired_group_id,
					'action'        => 'deactivate',
					'success'       => 0,
					'message'       => $result->get_error_message(),
				) );
				continue;
			}

			$synced++;
			MPMLS_Logger::log( array(
				'event'         => 'bulk_expired_sync',
				'email'         => $user->user_email,
				'wp_user_id'    => $user_id,
				'membership_id' => $product_id,
				'group_id'      => $expired_group_id,
				'action'        => 'deactivate',
				'success'       => 1,
				'message'       => 'Bulk sync: added to expired group.',
			) );
		}

		$new_offset = $offset + $batch;
		$done       = $new_offset >= $total;

		wp_send_json_success( array(
			'processed' => min( $new_offset, $total ),
			'total'     => $total,
			'synced'    => $synced,
			'skipped'   => $skipped,
			'errors'    => $errors,
			'done'      => $done,
			'offset'    => $new_offset,
		) );
	}

	public function ajax_sync_cancelled_members() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$cancelled_group_id = mpmls_get_setting( 'cancelled_group_id', '' );
		if ( $cancelled_group_id === '' ) {
			$cancelled_group_id = mpmls_get_setting( 'expired_group_id', '' );
		}
		if ( $cancelled_group_id === '' ) {
			wp_send_json_error( array( 'message' => 'Cancelled (or fallback expired) group ID is not configured.' ) );
		}

		$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch   = 5;
		$members = $this->get_cancelled_members();
		$total   = count( $members );
		$slice   = array_slice( $members, $offset, $batch );
		$synced  = 0;
		$skipped = 0;
		$errors  = 0;
		$client  = new MPMLS_MailerLite_Client( $api_key );

		foreach ( $slice as $member ) {
			$product_id = (int) $member['product_id'];
			$user_id    = (int) $member['user_id'];
			$expires_at = isset( $member['expires_at'] ) ? (string) $member['expires_at'] : '';

			$user = get_userdata( $user_id );
			if ( ! $user || empty( $user->user_email ) ) {
				$skipped++;
				continue;
			}

			$fields = $this->build_subscriber_fields( $user, $product_id, $expires_at, 'cancelled' );

			$subscriber_id = $client->upsert_subscriber( $user->user_email, $fields );
			if ( is_wp_error( $subscriber_id ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_cancelled_sync',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $cancelled_group_id,
					'action'        => 'deactivate',
					'success'       => 0,
					'message'       => $subscriber_id->get_error_message(),
				) );
				continue;
			}

			$result = $client->add_to_group( $subscriber_id, $cancelled_group_id, $user->user_email, $fields );
			if ( is_wp_error( $result ) ) {
				$errors++;
				MPMLS_Logger::log( array(
					'event'         => 'bulk_cancelled_sync',
					'email'         => $user->user_email,
					'wp_user_id'    => $user_id,
					'membership_id' => $product_id,
					'group_id'      => $cancelled_group_id,
					'action'        => 'deactivate',
					'success'       => 0,
					'message'       => $result->get_error_message(),
				) );
				continue;
			}

			$synced++;
			MPMLS_Logger::log( array(
				'event'         => 'bulk_cancelled_sync',
				'email'         => $user->user_email,
				'wp_user_id'    => $user_id,
				'membership_id' => $product_id,
				'group_id'      => $cancelled_group_id,
				'action'        => 'deactivate',
				'success'       => 1,
				'message'       => 'Bulk sync: added to cancelled group.',
			) );
		}

		$new_offset = $offset + $batch;
		$done       = $new_offset >= $total;

		wp_send_json_success( array(
			'processed' => min( $new_offset, $total ),
			'total'     => $total,
			'synced'    => $synced,
			'skipped'   => $skipped,
			'errors'    => $errors,
			'done'      => $done,
			'offset'    => $new_offset,
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
		global $wpdb;

		$sql = $this->get_active_members_sql( 0, true );
		if ( $sql === '' ) {
			return array();
		}

		return $wpdb->get_results(
			$sql,
			ARRAY_A
		);
	}

	protected function get_active_members_sql( $user_id = 0, $with_order = true ) {
		global $wpdb;

		$now   = current_time( 'mysql' );
		$parts = array();

		$subscriptions_table = $wpdb->prefix . 'mepr_subscriptions';
		$subscriptions_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subscriptions_table ) );
		if ( $subscriptions_exists === $subscriptions_table ) {
			$sql = "SELECT s.user_id, s.product_id, s.expires_at
				FROM {$subscriptions_table} s
				WHERE s.status = 'active'
				AND (s.expires_at IS NULL OR s.expires_at = '' OR s.expires_at = '0000-00-00 00:00:00' OR s.expires_at >= %s)";
			if ( $user_id ) {
				$sql .= ' AND s.user_id = %d';
				$sql = $wpdb->prepare( $sql, $now, $user_id );
			} else {
				$sql = $wpdb->prepare( $sql, $now );
			}
			$parts[] = $sql;
		}

		$subscription_id_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW COLUMNS FROM {$wpdb->prefix}mepr_transactions LIKE %s",
			'subscription_id'
		) );

		$sql = "SELECT t.user_id, t.product_id, t.expires_at
			FROM {$wpdb->prefix}mepr_transactions t
			WHERE t.status IN ('complete', 'confirmed')
			AND (t.expires_at IS NULL OR t.expires_at = '' OR t.expires_at = '0000-00-00 00:00:00' OR t.expires_at >= %s)";
		if ( $subscription_id_exists ) {
			$sql .= ' AND (t.subscription_id IS NULL OR t.subscription_id = 0)';
		}
		if ( $user_id ) {
			$sql .= ' AND t.user_id = %d';
			$sql = $wpdb->prepare( $sql, $now, $user_id );
		} else {
			$sql = $wpdb->prepare( $sql, $now );
		}
		$parts[] = $sql;

		if ( empty( $parts ) ) {
			return '';
		}

		$union = implode( ' UNION ALL ', $parts );
		$sql = "SELECT user_id, product_id, MAX(expires_at) AS expires_at
			FROM ( {$union} ) active_rows
			GROUP BY user_id, product_id";
		if ( $with_order ) {
			$sql .= ' ORDER BY user_id, product_id';
		}
		return $sql;
	}

	protected function get_mapping() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		if ( empty( $settings['mapping'] ) || ! is_array( $settings['mapping'] ) ) {
			return array();
		}
		return $settings['mapping'];
	}

	protected function get_active_membership_ids_for_user( $user_id ) {
		global $wpdb;

		if ( ! $user_id ) {
			return array();
		}

		$sql = $this->get_active_members_sql( (int) $user_id, false );
		if ( $sql === '' ) {
			return array();
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( empty( $rows ) ) {
			return array();
		}

		$ids = array();
		foreach ( $rows as $row ) {
			$ids[] = (int) $row['product_id'];
		}

		return array_values( array_unique( $ids ) );
	}

	protected function get_active_group_ids_for_user( $user_id ) {
		$mapping = $this->get_mapping();
		if ( empty( $mapping ) ) {
			return array();
		}

		$membership_ids = $this->get_active_membership_ids_for_user( $user_id );
		if ( empty( $membership_ids ) ) {
			return array();
		}

		$active = array();
		foreach ( $membership_ids as $membership_id ) {
			if ( isset( $mapping[ $membership_id ] ) ) {
				$group_id = (string) $mapping[ $membership_id ];
				if ( $group_id !== '' ) {
					$active[] = $group_id;
				}
			}
		}

		return array_values( array_unique( $active ) );
	}

	protected function remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $email, $event_name ) {
		$mapping = $this->get_mapping();
		if ( empty( $mapping ) ) {
			return;
		}

		$active_group_ids = $this->get_active_group_ids_for_user( $user_id );
		$active_group_ids = array_values( array_unique( $active_group_ids ) );

		$mapped_group_ids = array();
		foreach ( $mapping as $group_id ) {
			$mapped_group_ids[] = (string) $group_id;
		}
		$mapped_group_ids = array_values( array_unique( $mapped_group_ids ) );

		foreach ( $mapped_group_ids as $group_id ) {
			if ( $group_id === '' ) {
				continue;
			}
			if ( in_array( $group_id, $active_group_ids, true ) ) {
				continue;
			}

			$result = $client->remove_from_group( $subscriber_id, $group_id, $email );
			if ( is_wp_error( $result ) ) {
				MPMLS_Logger::log( array(
					'event'         => $event_name,
					'email'         => $email,
					'wp_user_id'    => (int) $user_id,
					'membership_id' => 0,
					'group_id'      => $group_id,
					'action'        => 'remove_inactive',
					'success'       => 0,
					'message'       => $result->get_error_message(),
				) );
				continue;
			}

			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $email,
				'wp_user_id'    => (int) $user_id,
				'membership_id' => 0,
				'group_id'      => $group_id,
				'action'        => 'remove_inactive',
				'success'       => 1,
				'message'       => 'Removed from inactive mapped group.',
			) );
		}
	}

	protected function get_expired_members() {
		global $wpdb;

		$sql = $this->get_expired_members_sql( true );
		if ( $sql === '' ) {
			return array();
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	protected function get_cancelled_members() {
		global $wpdb;

		$sql = $this->get_cancelled_members_sql( true );
		if ( $sql === '' ) {
			return array();
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	protected function get_expired_members_sql( $with_order = true ) {
		global $wpdb;

		$now   = current_time( 'mysql' );
		$parts = array();

		$subscriptions_table = $wpdb->prefix . 'mepr_subscriptions';
		$subscriptions_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subscriptions_table ) );
		if ( $subscriptions_exists === $subscriptions_table ) {
			$sql = "SELECT s.user_id, s.product_id, s.expires_at
				FROM {$subscriptions_table} s
				WHERE s.status = 'expired'";
			$parts[] = $sql;
		}

		$subscription_id_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW COLUMNS FROM {$wpdb->prefix}mepr_transactions LIKE %s",
			'subscription_id'
		) );

		$sql = "SELECT t.user_id, t.product_id, t.expires_at
			FROM {$wpdb->prefix}mepr_transactions t
			WHERE (
				(t.status IN ('complete', 'confirmed')
					AND t.expires_at IS NOT NULL
					AND t.expires_at <> ''
					AND t.expires_at <> '0000-00-00 00:00:00'
					AND t.expires_at < %s)
				OR t.status IN ('expired')
			)";
		if ( $subscription_id_exists ) {
			$sql .= ' AND (t.subscription_id IS NULL OR t.subscription_id = 0)';
		}
		$sql = $wpdb->prepare( $sql, $now );
		$parts[] = $sql;

		if ( empty( $parts ) ) {
			return '';
		}

		$union = implode( ' UNION ALL ', $parts );
		$sql = "SELECT user_id, product_id, MAX(expires_at) AS expires_at
			FROM ( {$union} ) expired_rows
			GROUP BY user_id, product_id";
		if ( $with_order ) {
			$sql .= ' ORDER BY user_id, product_id';
		}
		return $sql;
	}

	protected function get_cancelled_members_sql( $with_order = true ) {
		global $wpdb;

		$parts = array();

		$subscriptions_table = $wpdb->prefix . 'mepr_subscriptions';
		$subscriptions_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subscriptions_table ) );
		if ( $subscriptions_exists === $subscriptions_table ) {
			$sql = "SELECT s.user_id, s.product_id, s.expires_at
				FROM {$subscriptions_table} s
				WHERE s.status IN ('cancelled', 'suspended', 'stopped')";
			$parts[] = $sql;
		}

		$subscription_id_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW COLUMNS FROM {$wpdb->prefix}mepr_transactions LIKE %s",
			'subscription_id'
		) );

		$sql = "SELECT t.user_id, t.product_id, t.expires_at
			FROM {$wpdb->prefix}mepr_transactions t
			WHERE t.status IN ('refunded')";
		if ( $subscription_id_exists ) {
			$sql .= ' AND (t.subscription_id IS NULL OR t.subscription_id = 0)';
		}
		$parts[] = $sql;

		if ( empty( $parts ) ) {
			return '';
		}

		$union = implode( ' UNION ALL ', $parts );
		$sql = "SELECT user_id, product_id, MAX(expires_at) AS expires_at
			FROM ( {$union} ) cancelled_rows
			GROUP BY user_id, product_id";
		if ( $with_order ) {
			$sql .= ' ORDER BY user_id, product_id';
		}
		return $sql;
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

		$active_counts    = $this->get_member_counts( $this->get_active_members_sql( 0, false ) );
		$expired_counts   = $this->get_member_counts( $this->get_expired_members_sql( false ) );
		$cancelled_counts = $this->get_member_counts( $this->get_cancelled_members_sql( false ) );

		return array(
			'subscriptions_table'            => $subscriptions_exists,
			'transactions_table'             => $transactions_exists,
			'subscriptions_expires_column'   => $subscriptions_expires,
			'transactions_expires_column'    => $transactions_expires,
			'transactions_subscription_column' => $transactions_subscription,
			'active_memberships'             => $active_counts['memberships'],
			'expired_memberships'            => $expired_counts['memberships'],
			'cancelled_memberships'          => $cancelled_counts['memberships'],
			'subscription_statuses'          => $subscription_statuses,
			'transaction_statuses'           => $transaction_statuses,
		);
	}

	protected function build_subscriber_fields( $user, $product_id, $expires_at, $status ) {
		$fields = array(
			'name'              => $user->first_name ?: '',
			'last_name'         => $user->last_name ?: '',
			'membership_name'   => $product_id ? ( get_the_title( $product_id ) ?: '' ) : '',
			'membership_status' => (string) $status,
		);
		if ( $user->user_registered ) {
			$fields['signup_date'] = date( 'Y-m-d', strtotime( $user->user_registered ) );
		}
		if ( $expires_at && $expires_at !== '0000-00-00 00:00:00' ) {
			$fields['membership_expiry'] = date( 'Y-m-d', strtotime( $expires_at ) );
		}
		return array_filter( $fields, function ( $v ) {
			return $v !== '';
		} );
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
