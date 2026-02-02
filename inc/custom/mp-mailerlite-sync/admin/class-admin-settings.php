<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPMLS_Admin_Settings {
	const PAGE_SLUG = 'mpmls-settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_mpmls_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_mpmls_send_test_event', array( $this, 'ajax_send_test_event' ) );
		add_action( 'admin_post_mpmls_clear_logs', array( $this, 'handle_clear_logs' ) );
	}

	public function register_menu() {
		add_options_page(
			'MP - MailerLite',
			'MP - MailerLite',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( 'mpmls_settings_group', MPMLS_OPTION_KEY, array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $input ) {
		$output = array();

		$output['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';

		$output['expired_group_id'] = isset( $input['expired_group_id'] ) ? sanitize_text_field( $input['expired_group_id'] ) : '';
		$output['logging_enabled']  = ! empty( $input['logging_enabled'] ) ? 1 : 0;
		$output['remove_on_expired'] = ! empty( $input['remove_on_expired'] ) ? 1 : 0;

		$mapping = array();
		if ( ! empty( $input['mapping'] ) && is_array( $input['mapping'] ) ) {
			foreach ( $input['mapping'] as $row ) {
				$membership_id = isset( $row['membership_id'] ) ? absint( $row['membership_id'] ) : 0;
				$group_id      = isset( $row['group_id'] ) ? sanitize_text_field( $row['group_id'] ) : '';
				if ( $membership_id && $group_id !== '' ) {
					$mapping[ $membership_id ] = $group_id;
				}
			}
		}
		$output['mapping'] = $mapping;

		return $output;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings         = get_option( MPMLS_OPTION_KEY, array() );
		$api_key          = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$expired_group_id = isset( $settings['expired_group_id'] ) ? $settings['expired_group_id'] : '';
		$logging_enabled  = ! empty( $settings['logging_enabled'] );
		$remove_on_expired = ! empty( $settings['remove_on_expired'] );
		$mapping          = isset( $settings['mapping'] ) && is_array( $settings['mapping'] ) ? $settings['mapping'] : array();

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
		$connection_ok = ! empty( $connection_status['ok'] ) && ! empty( $connection_status['key_hash'] ) && $connection_status['key_hash'] === md5( $api_key );

		$products = $this->get_memberpress_products();
		$groups_result = $this->get_mailerlite_groups( $api_key );
		$groups_error = is_wp_error( $groups_result ) ? $groups_result->get_error_message() : '';
		$groups = is_wp_error( $groups_result ) ? array() : $groups_result;

		$product_options = $this->render_product_options( $products, '' );
		$group_options = $this->render_group_options( $groups, '' );

		$logs = $this->get_logs();
		$event_filter = isset( $_GET['mpmls_event'] ) ? sanitize_text_field( wp_unslash( $_GET['mpmls_event'] ) ) : '';
		$events = $this->get_log_events();

		?>
		<div class="wrap mpmls-wrap">
			<style>
				.mpmls-wrap .form-table th { width: 260px; }
				.mpmls-wrap .form-table td { padding-top: 14px; padding-bottom: 14px; }
				.mpmls-wrap .form-table .description { margin-top: 6px; }
				.mpmls-wrap .mpmls-inline-actions { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
				.mpmls-wrap #mpmls-test-result { display: inline-block; min-width: 120px; }
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
				.mpmls-wrap .mpmls-inline-actions { flex-wrap: wrap; }
				.mpmls-wrap .mpmls-quick-actions { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
			</style>
			<h1>MP - MailerLite</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'mpmls_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mpmls_api_key">MailerLite API key</label></th>
						<td>
							<input type="password" id="mpmls_api_key" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[api_key]" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="new-password" />
							<p class="description">Use a MailerLite API key (classic or new). The plugin auto-detects the API type.</p>
							<?php if ( $connection_ok ) : ?>
								<p class="description"><strong>Status:</strong> Connected<?php echo ! empty( $connection_status['time'] ) ? ' (last checked ' . esc_html( $connection_status['time'] ) . ')' : ''; ?>.</p>
							<?php endif; ?>
							<div class="mpmls-inline-actions">
								<button type="button" class="button" id="mpmls-test-connection" data-nonce="<?php echo esc_attr( $nonce ); ?>">Test connection</button>
								<span id="mpmls-test-result"></span>
							</div>
						</td>
					</tr>
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
						<th scope="row"><label for="mpmls-expired-group">Expired/Cancelled group ID</label></th>
						<td>
							<?php if ( ! empty( $groups ) ) : ?>
								<select id="mpmls-expired-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[expired_group_id]" class="regular-text">
									<?php echo $this->render_group_options( $groups, $expired_group_id, true ); ?>
								</select>
							<?php else : ?>
								<input type="text" id="mpmls-expired-group" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[expired_group_id]" value="<?php echo esc_attr( $expired_group_id ); ?>" class="regular-text" />
							<?php endif; ?>
							<p class="description">Optional group ID to add expired/cancelled users.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Logging</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[logging_enabled]" value="1" <?php checked( $logging_enabled ); ?> /> Enable logging</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Remove from active groups when expired/cancelled</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( MPMLS_OPTION_KEY ); ?>[remove_on_expired]" value="1" <?php checked( $remove_on_expired ); ?> /> Remove from mapped groups on cancel/expire</label>
						</td>
					</tr>
				</table>
				<div class="mpmls-quick-actions">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
					<button type="button" class="button" id="mpmls-test-event">Send test event</button>
				</div>
			</form>

			<hr class="mpmls-section-spacer" />
			<h2>Logs</h2>
			<form method="get" class="mpmls-logs-actions">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>" />
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
			});

			$('#mpmls-test-connection').on('click', function(){
				var $result = $('#mpmls-test-result');
				$result.text('Testing...');
				$.post(ajaxurl, {
					action: 'mpmls_test_connection',
					nonce: $(this).data('nonce')
				}, function(response){
					if(response.success){
						$result.text('Success: ' + response.data.message);
						setTimeout(function(){ location.reload(); }, 600);
					} else {
						$result.text('Error: ' + response.data.message);
					}
				});
			});

			$('#mpmls-test-event').on('click', function(){
				var $result = $('#mpmls-test-result');
				$result.text('Sending test...');
				$.post(ajaxurl, {
					action: 'mpmls_send_test_event',
					nonce: $(this).data('nonce')
				}, function(response){
					if(response.success){
						$result.text('Success: ' + response.data.message);
					} else {
						$result.text('Error: ' + response.data.message);
					}
				});
			});
		});
		</script>
		<?php
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$client = new MPMLS_MailerLite_Client( $api_key );
		$response = $client->test_connection();
		if ( is_wp_error( $response ) ) {
			update_option( 'mpmls_connection_status', array( 'ok' => false, 'key_hash' => md5( $api_key ) ) );
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		update_option( 'mpmls_connection_status', array( 'ok' => true, 'key_hash' => md5( $api_key ), 'time' => current_time( 'mysql' ) ) );
		wp_send_json_success( array( 'message' => 'Connection OK.' ) );
	}

	public function ajax_send_test_event() {
		check_ajax_referer( 'mpmls_test_connection', 'nonce' );

		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'MailerLite API key is missing.' ) );
		}

		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$mapping = isset( $settings['mapping'] ) && is_array( $settings['mapping'] ) ? $settings['mapping'] : array();
		if ( empty( $mapping ) ) {
			wp_send_json_error( array( 'message' => 'No membership → group mapping found.' ) );
		}

		$first_membership_id = (int) array_key_first( $mapping );
		$group_id = (string) $mapping[ $first_membership_id ];
		if ( ! $first_membership_id || $group_id === '' ) {
			wp_send_json_error( array( 'message' => 'Invalid mapping data.' ) );
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->user_email ) ) {
			wp_send_json_error( array( 'message' => 'No current user email found.' ) );
		}

		$client = new MPMLS_MailerLite_Client( $api_key );
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

	public function handle_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( 'mpmls_clear_logs' );

		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . MPMLS_Logger::table_name() );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	protected function get_logs() {
		global $wpdb;
		$table = MPMLS_Logger::table_name();
		$event_filter = isset( $_GET['mpmls_event'] ) ? sanitize_text_field( wp_unslash( $_GET['mpmls_event'] ) ) : '';

		$sql = "SELECT * FROM {$table}";
		$params = array();
		if ( $event_filter !== '' ) {
			$sql .= ' WHERE event = %s';
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
		$cached = get_transient( $cache_key );
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
		$options = '<option value=\"\">Select product</option>';
		$found = false;
		foreach ( $products as $product ) {
			$value = (string) $product['id'];
			$is_selected = selected( $selected, $value, false );
			if ( $is_selected ) {
				$found = true;
			}
			$options .= '<option value=\"' . esc_attr( $value ) . '\" ' . $is_selected . '>' . esc_html( $product['title'] . ' (#' . $value . ')' ) . '</option>';
		}
		if ( $selected !== '' && ! $found ) {
			$options = '<option value=\"' . esc_attr( $selected ) . '\" selected>Unknown product (#' . esc_html( $selected ) . ')</option>' . $options;
		}
		return $options;
	}

	protected function render_group_options( $groups, $selected, $allow_empty = false ) {
		$selected = (string) $selected;
		$options = $allow_empty ? '<option value=\"\">No group</option>' : '<option value=\"\">Select group</option>';
		$found = false;
		foreach ( $groups as $group ) {
			$value = (string) $group['id'];
			$is_selected = selected( $selected, $value, false );
			if ( $is_selected ) {
				$found = true;
			}
			$options .= '<option value=\"' . esc_attr( $value ) . '\" ' . $is_selected . '>' . esc_html( $group['name'] . ' (#' . $value . ')' ) . '</option>';
		}
		if ( $selected !== '' && ! $found ) {
			$options = '<option value=\"' . esc_attr( $selected ) . '\" selected>Unknown group (#' . esc_html( $selected ) . ')</option>' . $options;
		}
		return $options;
	}


	protected function get_log_events() {
		global $wpdb;
		$table = MPMLS_Logger::table_name();
		return $wpdb->get_col( "SELECT DISTINCT event FROM {$table} ORDER BY event ASC" );
	}
}
