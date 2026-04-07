<?php
/**
 * Admin setting: Member redirect URL.
 * Lets you override where logged-in users are redirected when they visit the homepage.
 */
add_action( 'admin_init', 'zaher_register_member_redirect_url_setting' );
function zaher_register_member_redirect_url_setting() {
	register_setting(
		'general',
		'zaher_member_katalog_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		)
	);

	add_settings_field(
		'zaher_member_katalog_url',
		'Member redirect URL',
		'zaher_render_member_redirect_url_setting_field',
		'general'
	);
}

function zaher_render_member_redirect_url_setting_field() {
	$value = (string) get_option( 'zaher_member_katalog_url', '' );
	echo '<input type="url" class="regular-text ltr" id="zaher_member_katalog_url" name="zaher_member_katalog_url" value="' . esc_attr( $value ) . '" placeholder="https://localhost:3000/katalog/" />';
	echo '<p class="description">If set, logged-in users visiting the homepage will be redirected to this URL (e.g. <code>https://localhost:3000/katalog/</code>). Leave empty to use the normal Katalog permalink.</p>';
}

add_action( 'admin_menu', 'zaher_register_checkout_popup_settings_page' );
function zaher_register_checkout_popup_settings_page() {
	if ( ! class_exists( 'MeprAppCtrl' ) ) {
		return;
	}

	add_submenu_page(
		'memberpress',
		'Checkout Popup',
		'Checkout Popup',
		'manage_options',
		'zaher-checkout-popup-settings',
		'zaher_render_checkout_popup_settings_page'
	);
}

add_action( 'admin_menu', 'zaher_reorder_checkout_popup_settings_page', 999 );
function zaher_reorder_checkout_popup_settings_page() {
	global $submenu;

	if ( empty( $submenu['memberpress'] ) || ! is_array( $submenu['memberpress'] ) ) {
		return;
	}

	$target_slug = 'zaher-checkout-popup-settings';
	$target_item = null;
	$new_order   = array();

	foreach ( $submenu['memberpress'] as $item ) {
		if ( is_array( $item ) && isset( $item[2] ) && $item[2] === $target_slug ) {
			$target_item = $item;
		}
	}

	if ( ! $target_item ) {
		return;
	}

	foreach ( $submenu['memberpress'] as $item ) {
		if ( is_array( $item ) && isset( $item[2] ) && $item[2] === $target_slug ) {
			continue;
		}

		$new_order[] = $item;

		if ( is_array( $item ) && isset( $item[2] ) && $item[2] === 'memberpress-options' ) {
			$new_order[] = $target_item;
			$target_item = null;
		}
	}

	if ( null !== $target_item ) {
		$new_order[] = $target_item;
	}

	$submenu['memberpress'] = $new_order;
}

add_action( 'admin_enqueue_scripts', 'zaher_enqueue_checkout_popup_admin_assets' );
function zaher_enqueue_checkout_popup_admin_assets( $hook_suffix ) {
	$is_checkout_popup_page = 'memberpress_page_zaher-checkout-popup-settings' === $hook_suffix;

	if ( ! $is_checkout_popup_page && ( ! isset( $_GET['page'] ) || 'zaher-checkout-popup-settings' !== $_GET['page'] ) ) {
		return;
	}

	if ( function_exists( 'wp_enqueue_editor' ) ) {
		wp_enqueue_editor();
	}
}

add_action( 'admin_init', 'zaher_register_checkout_popup_settings' );
function zaher_register_checkout_popup_settings() {
	register_setting(
		'zaher_checkout_popup_settings',
		'zaher_checkout_popups',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'zaher_sanitize_checkout_popups',
			'default'           => array(),
		)
	);
}

function zaher_sanitize_checkout_popups( $value ) {
	$field_map     = zaher_get_checkout_popup_custom_copy_field_map();
	$default_key   = zaher_get_checkout_popup_default_template_key();
	$rows          = is_array( $value ) ? $value : array();
	$sanitized     = array();
	$seen_sources  = array();
	$row_number    = 0;

	foreach ( $rows as $key => $row ) {
		if ( '_present' === $key || ! is_array( $row ) ) {
			continue;
		}

		++$row_number;

		$template_key      = $default_key;
		$source_product_id = isset( $row['source_product_id'] ) ? absint( $row['source_product_id'] ) : 0;
		$target_product_id = isset( $row['target_product_id'] ) ? absint( $row['target_product_id'] ) : 0;
		$coupon_code       = isset( $row['coupon_code'] ) ? sanitize_text_field( $row['coupon_code'] ) : '';
		$enabled           = ! isset( $row['enabled'] ) || '0' !== (string) $row['enabled'];
		$custom_copy       = array();

		foreach ( $field_map as $row_key => $template_field ) {
			$custom_copy[ $row_key ] = isset( $row[ $row_key ] ) ? wp_kses_post( wp_unslash( $row[ $row_key ] ) ) : '';
		}

		if ( isset( $row['custom_body_html'] ) ) {
			$custom_copy['custom_subtitle_html'] = zaher_merge_checkout_popup_content_html(
				$custom_copy['custom_subtitle_html'],
				wp_kses_post( wp_unslash( $row['custom_body_html'] ) )
			);
		}

		if ( ! $source_product_id && ! $target_product_id && '' === $coupon_code ) {
			continue;
		}

		if ( ! $source_product_id || ! $target_product_id ) {
			add_settings_error( 'zaher_checkout_popups', 'zaher_popup_missing_products_' . $row_number, sprintf( 'Popup #%d nije spremljen jer nedostaje izvorna ili ciljana pretplata.', $row_number ), 'error' );
			continue;
		}

		if ( $source_product_id === $target_product_id ) {
			add_settings_error( 'zaher_checkout_popups', 'zaher_popup_same_products_' . $row_number, sprintf( 'Popup #%d nije spremljen jer izvorna i ciljana pretplata ne mogu biti iste.', $row_number ), 'error' );
			continue;
		}

		$source_product = zaher_get_checkout_popup_product( $source_product_id );
		$target_product = zaher_get_checkout_popup_product( $target_product_id );

		if ( ! $source_product || ! $target_product ) {
			add_settings_error( 'zaher_checkout_popups', 'zaher_popup_invalid_products_' . $row_number, sprintf( 'Popup #%d nije spremljen jer jedna od odabranih pretplata više ne postoji.', $row_number ), 'error' );
			continue;
		}

		if ( isset( $seen_sources[ $source_product_id ] ) ) {
			add_settings_error( 'zaher_checkout_popups', 'zaher_popup_duplicate_source_' . $row_number, sprintf( 'Popup #%d nije spremljen jer već postoji popup za isti checkout.', $row_number ), 'error' );
			continue;
		}

		if ( $coupon_code && class_exists( 'MeprCoupon' ) && ! MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product_id ) ) {
			add_settings_error( 'zaher_checkout_popups', 'zaher_popup_invalid_coupon_' . $row_number, sprintf( 'Kupon za popup #%d nije valjan za odabranu ciljanu pretplatu pa je uklonjen.', $row_number ), 'warning' );
			$coupon_code = '';
		}

			$sanitized[] = array_merge(
				array(
					'template_key'      => $template_key,
					'source_product_id' => $source_product_id,
					'target_product_id' => $target_product_id,
					'coupon_code'       => $coupon_code,
					'enabled'           => $enabled ? 1 : 0,
				),
				$custom_copy
			);

		$seen_sources[ $source_product_id ] = true;
	}

	return array_values( $sanitized );
}

function zaher_get_checkout_popup_product_choices() {
	$choices = array();

	if ( ! class_exists( 'MeprProduct' ) ) {
		return $choices;
	}

	$product_ids = get_posts(
		array(
			'post_type'      => MeprProduct::$cpt,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	foreach ( $product_ids as $product_id ) {
		$product = zaher_get_checkout_popup_product( $product_id );

		if ( ! $product ) {
			continue;
		}

		$choices[ (string) $product->ID ] = array(
			'id'               => (int) $product->ID,
			'title'            => get_the_title( $product->ID ),
			'label'            => sprintf( '%s (%s)', get_the_title( $product->ID ), zaher_get_checkout_popup_new_price_text( $product ) ),
			'price'            => (float) $product->price,
			'period'           => (int) $product->period,
			'periodType'       => (string) $product->period_type,
			'isOneTime'        => (bool) $product->is_one_time_payment(),
			'shortPeriodLabel' => zaher_get_checkout_popup_short_period_label( $product ),
			'url'              => $product->url(),
		);
	}

	return $choices;
}

function zaher_get_checkout_popup_coupon_option_label( $coupon ) {
	if ( ! $coupon instanceof MeprCoupon ) {
		return '';
	}

	$code = get_the_title( $coupon->ID );

	if ( 'trial-override' === $coupon->discount_mode ) {
		return sprintf( '%s (trial override)', $code );
	}

	if ( 'first-payment' === $coupon->discount_mode ) {
		$amount = 'percent' === $coupon->first_payment_discount_type
			? rtrim( rtrim( number_format( (float) $coupon->first_payment_discount_amount, 2, '.', '' ), '0' ), '.' ) . '%'
			: MeprAppHelper::format_currency( $coupon->first_payment_discount_amount, true, false );

		return sprintf( '%s (prva uplata: %s)', $code, $amount );
	}

	$amount = 'percent' === $coupon->discount_type
		? rtrim( rtrim( number_format( (float) $coupon->discount_amount, 2, '.', '' ), '0' ), '.' ) . '%'
		: MeprAppHelper::format_currency( $coupon->discount_amount, true, false );

	return sprintf( '%s (%s)', $code, $amount );
}

function zaher_get_checkout_popup_coupon_choices() {
	$choices = array();

	if ( ! class_exists( 'MeprCoupon' ) ) {
		return $choices;
	}

	$coupon_ids = get_posts(
		array(
			'post_type'      => MeprCoupon::$cpt,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	foreach ( $coupon_ids as $coupon_id ) {
		$coupon = new MeprCoupon( $coupon_id );

		if ( empty( $coupon->ID ) ) {
			continue;
		}

		$code = get_the_title( $coupon->ID );

		$choices[ $code ] = array(
			'code'                     => $code,
			'label'                    => zaher_get_checkout_popup_coupon_option_label( $coupon ),
			'validProductIds'          => array_map( 'intval', is_array( $coupon->valid_products ) ? $coupon->valid_products : array() ),
			'appliesToAllProducts'     => empty( $coupon->valid_products ),
			'discountMode'             => (string) $coupon->discount_mode,
			'discountType'             => (string) $coupon->discount_type,
			'discountAmount'           => (float) $coupon->discount_amount,
			'firstPaymentDiscountType' => (string) $coupon->first_payment_discount_type,
			'firstPaymentDiscountAmount' => (float) $coupon->first_payment_discount_amount,
		);
	}

	return $choices;
}

function zaher_render_checkout_popup_select_options( $items, $selected, $placeholder ) {
	echo '<option value="">' . esc_html( $placeholder ) . '</option>';

	foreach ( $items as $value => $item ) {
		$label = is_array( $item ) && isset( $item['label'] ) ? $item['label'] : $value;
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $value ),
			selected( (string) $selected, (string) $value, false ),
			esc_html( $label )
		);
	}
}

function zaher_get_checkout_popup_custom_copy_fields() {
	return array(
		'custom_title_html' => array(
			'templateField' => 'title_html',
			'label'         => 'Naslov',
			'type'          => 'textarea',
			'rows'          => 3,
			'wide'          => true,
			'rich'          => true,
			'description'   => 'Naslov popupa. Koristi rich editor za bold, prijelome redaka i osnovno formatiranje.',
		),
		'custom_subtitle_html' => array(
			'templateField' => 'subtitle_html',
			'label'         => 'Sadržaj',
			'type'          => 'textarea',
			'rows'          => 8,
			'wide'          => true,
			'rich'          => true,
			'description'   => 'Glavni sadržaj ispod naslova. Koristi rich editor za bold, linkove, liste i prijelome.',
		),
	);
}

function zaher_render_checkout_popup_row( $index, $popup, $source_products, $target_products, $coupon_choices ) {
	$source_product_id = isset( $popup['source_product_id'] ) ? absint( $popup['source_product_id'] ) : 0;
	$target_product_id = isset( $popup['target_product_id'] ) ? absint( $popup['target_product_id'] ) : 0;
	$coupon_code       = isset( $popup['coupon_code'] ) ? sanitize_text_field( $popup['coupon_code'] ) : '';
	$enabled           = ! isset( $popup['enabled'] ) || ! empty( $popup['enabled'] );
	$custom_copy       = zaher_get_checkout_popup_row_custom_copy( $popup );
	$custom_fields     = zaher_get_checkout_popup_custom_copy_fields();
	?>
	<div class="zaher-popup-card<?php echo $enabled ? '' : ' is-disabled'; ?>" data-popup-row>
		<div class="zaher-popup-card__header">
			<div>
				<h2 class="zaher-popup-card__title" data-popup-title>Popup</h2>
				<p class="zaher-popup-card__subtitle" data-popup-subtitle>Odaberi checkout na kojem se popup prikazuje i pretplatu na koju vodi CTA.</p>
			</div>
			<div class="zaher-popup-card__actions">
				<label class="zaher-popup-toggle">
					<input type="hidden" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][enabled]" value="0" />
					<input type="checkbox" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( $enabled ); ?> data-popup-enabled />
					<span class="zaher-popup-toggle__track" aria-hidden="true"></span>
					<span class="zaher-popup-toggle__label" data-popup-enabled-label><?php echo $enabled ? 'Uključen' : 'Isključen'; ?></span>
				</label>
				<button type="button" class="button-link-delete" data-remove-popup>Ukloni</button>
			</div>
		</div>

		<div class="zaher-popup-card__layout">
			<div class="zaher-popup-card__form">
				<section class="zaher-popup-card__section">
					<div class="zaher-popup-card__section-header">
						<p class="zaher-popup-card__eyebrow">Osnovno</p>
						<h3>Povezivanje checkouta i ponude</h3>
						<p>Izvorišni checkout aktivira popup, a ciljna pretplata i kupon definiraju cijenu i CTA odredište.</p>
					</div>

					<div class="zaher-popup-card__grid">
						<div class="zaher-popup-field">
							<label for="zaher-popup-source-<?php echo esc_attr( $index ); ?>">Prikaži na checkoutu</label>
							<select id="zaher-popup-source-<?php echo esc_attr( $index ); ?>" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][source_product_id]" data-popup-source>
								<?php zaher_render_checkout_popup_select_options( $source_products, $source_product_id, 'Odaberi pretplatu' ); ?>
							</select>
							<p class="description">Možeš vezati popup uz bilo koji postojeći MemberPress checkout.</p>
						</div>

						<div class="zaher-popup-field">
							<label for="zaher-popup-target-<?php echo esc_attr( $index ); ?>">CTA vodi na</label>
							<select id="zaher-popup-target-<?php echo esc_attr( $index ); ?>" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][target_product_id]" data-popup-target>
								<?php zaher_render_checkout_popup_select_options( $target_products, $target_product_id, 'Odaberi ciljanu pretplatu' ); ?>
							</select>
							<p class="description">CTA URL se generira automatski iz odabrane ciljane pretplate.</p>
						</div>

						<div class="zaher-popup-field">
							<label for="zaher-popup-coupon-<?php echo esc_attr( $index ); ?>">Kupon</label>
							<select id="zaher-popup-coupon-<?php echo esc_attr( $index ); ?>" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][coupon_code]" data-popup-coupon>
								<?php zaher_render_checkout_popup_select_options( $coupon_choices, $coupon_code, 'Bez kupona' ); ?>
							</select>
							<p class="description">Ako je kupon valjan za ciljanu pretplatu, cijene i CTA URL računaju se automatski preko njega.</p>
						</div>
					</div>
				</section>

					<section class="zaher-popup-card__section">
						<div class="zaher-popup-card__section-header">
							<p class="zaher-popup-card__eyebrow">Sadržaj</p>
							<h3>Naslov i sadržaj popupa</h3>
							<p>Ovdje upisuješ vlastiti naslov i sadržaj. Preview i stvarni popup koriste ovaj sadržaj umjesto default copyja.</p>
						</div>

						<div class="zaher-popup-card__grid">
							<?php foreach ( $custom_fields as $field_key => $field_config ) : ?>
								<div class="zaher-popup-field<?php echo ! empty( $field_config['wide'] ) ? ' is-wide' : ''; ?>">
								<label for="zaher-popup-<?php echo esc_attr( $field_key ); ?>-<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $field_config['label'] ); ?></label>
								<textarea
									id="zaher-popup-<?php echo esc_attr( $field_key ); ?>-<?php echo esc_attr( $index ); ?>"
									name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][<?php echo esc_attr( $field_key ); ?>]"
									rows="<?php echo esc_attr( isset( $field_config['rows'] ) ? (int) $field_config['rows'] : 4 ); ?>"
									data-popup-custom-input="<?php echo esc_attr( $field_config['templateField'] ); ?>"
									<?php echo ! empty( $field_config['rich'] ) ? 'data-popup-rich-editor="1"' : ''; ?>
								><?php echo esc_textarea( isset( $custom_copy[ $field_config['templateField'] ] ) ? $custom_copy[ $field_config['templateField'] ] : '' ); ?></textarea>
								<p class="description"><?php echo esc_html( $field_config['description'] ); ?></p>
							</div>
							<?php endforeach; ?>
						</div>
					</section>
				</div>

			<aside class="zaher-popup-card__preview-pane">
				<div class="zaher-popup-card__preview-header">
					<div>
						<p class="zaher-popup-card__eyebrow">Preview</p>
						<h3>Live prikaz popupa</h3>
					</div>
					<span class="zaher-popup-card__preview-note">Ažurira se odmah</span>
				</div>
				<div class="zaher-popup-card__preview" data-popup-preview></div>
			</aside>
		</div>
	</div>
	<?php
}

function zaher_render_checkout_popup_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$source_products = zaher_get_checkout_popup_product_choices();
	$target_products = zaher_get_checkout_popup_product_choices();
	$coupon_choices  = zaher_get_checkout_popup_coupon_choices();
	$template_choices = zaher_get_checkout_popup_template_choices();
	$default_template = zaher_get_checkout_popup_default_template_key();
	$saved_rows      = zaher_get_saved_checkout_popup_rows();
	$rows            = is_array( $saved_rows ) && ! empty( $saved_rows ) ? array_values( $saved_rows ) : array(
		array(
			'template_key'      => $default_template,
			'source_product_id' => 0,
			'target_product_id' => 0,
			'coupon_code'       => '',
			'enabled'           => 1,
		),
	);
	$currency_symbol = class_exists( 'MeprOptions' ) ? MeprOptions::fetch()->currency_symbol : '€';
	$currency_after  = class_exists( 'MeprOptions' ) ? (bool) MeprOptions::fetch()->currency_symbol_after : true;
	$admin_data      = array(
		'sourceProducts' => $source_products,
		'targetProducts' => $target_products,
		'coupons'        => $coupon_choices,
		'templates'      => $template_choices,
		'defaultTemplateKey' => $default_template,
		'currency'       => array(
			'symbol' => $currency_symbol,
			'after'  => $currency_after,
		),
	);
	?>
	<div class="wrap zaher-popup-settings">
		<style>
			.zaher-popup-settings {
				--zaher-popup-bg: #f5f1ea;
				--zaher-popup-surface: #ffffff;
				--zaher-popup-surface-alt: #fcfaf6;
				--zaher-popup-border: #e6ddd2;
				--zaher-popup-text: #1f2937;
				--zaher-popup-muted: #6b7280;
				--zaher-popup-accent: #c2410c;
				--zaher-popup-accent-soft: #fff4eb;
				--zaher-popup-accent-strong: #ec4899;
				--zaher-popup-shadow: 0 28px 70px -42px rgba(65, 31, 14, 0.38);
				max-width: 1440px;
				margin-right: 24px;
				color: var(--zaher-popup-text);
			}
			.zaher-popup-settings .zaher-popup-settings__hero {
				display: grid;
				grid-template-columns: minmax(0, 1.35fr) auto;
				gap: 24px;
				align-items: center;
				padding: 28px 30px;
				border: 1px solid rgba(255, 255, 255, 0.45);
				border-radius: 28px;
				background:
					radial-gradient(circle at top left, rgba(255, 255, 255, 0.34), transparent 38%),
					linear-gradient(135deg, #8a3b12 0%, #c65a13 52%, #e2833f 100%);
				color: #fff7ed;
				box-shadow: 0 30px 80px -48px rgba(95, 37, 11, 0.8);
				overflow: hidden;
				position: relative;
			}
			.zaher-popup-settings .zaher-popup-settings__hero::after {
				content: "";
				position: absolute;
				inset: auto -80px -80px auto;
				width: 240px;
				height: 240px;
				border-radius: 50%;
				background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 72%);
				pointer-events: none;
			}
			.zaher-popup-settings .zaher-popup-settings__hero h1 {
				margin: 0 0 10px;
				font-size: 34px;
				line-height: 1.08;
				color: #ffffff;
			}
			.zaher-popup-settings .zaher-popup-settings__hero p {
				margin: 0;
				max-width: 780px;
				font-size: 15px;
				line-height: 1.7;
				color: rgba(255, 247, 237, 0.88);
			}
			.zaher-popup-settings .zaher-popup-settings__eyebrow,
			.zaher-popup-settings .zaher-popup-card__eyebrow {
				margin: 0 0 8px;
				font-size: 11px;
				font-weight: 700;
				line-height: 1;
				letter-spacing: 0.18em;
				text-transform: uppercase;
			}
			.zaher-popup-settings .zaher-popup-settings__eyebrow {
				color: rgba(255, 247, 237, 0.78);
			}
			.zaher-popup-settings .zaher-popup-settings__stats {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 14px;
				min-width: min(360px, 100%);
			}
			.zaher-popup-settings .zaher-popup-settings__stat {
				padding: 16px 18px;
				border-radius: 18px;
				background: rgba(255, 255, 255, 0.16);
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
				border: 1px solid rgba(255, 255, 255, 0.16);
				box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
			}
			.zaher-popup-settings .zaher-popup-settings__stat strong {
				display: block;
				font-size: 28px;
				line-height: 1;
				color: #ffffff;
			}
			.zaher-popup-settings .zaher-popup-settings__stat span {
				display: block;
				margin-top: 6px;
				font-size: 12px;
				letter-spacing: 0.08em;
				text-transform: uppercase;
				color: rgba(255, 247, 237, 0.8);
			}
			.zaher-popup-settings .notice,
			.zaher-popup-settings .settings-error {
				margin: 18px 0 0;
			}
			.zaher-popup-settings .zaher-popup-settings__toolbar {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 16px;
				margin: 26px 0 18px;
				padding: 18px 22px;
				border-radius: 22px;
				background: var(--zaher-popup-surface);
				border: 1px solid var(--zaher-popup-border);
				box-shadow: var(--zaher-popup-shadow);
			}
			.zaher-popup-settings .zaher-popup-settings__toolbar p {
				margin: 0;
				max-width: 760px;
				color: var(--zaher-popup-muted);
			}
			.zaher-popup-settings .zaher-popup-settings__toolbar .button {
				height: auto;
				padding: 10px 18px;
				border-radius: 999px;
				border: 1px solid rgba(194, 65, 12, 0.28);
				background: linear-gradient(120deg, rgba(255, 244, 235, 0.98), rgba(255, 255, 255, 0.98));
				color: var(--zaher-popup-accent);
				font-weight: 600;
				box-shadow: 0 14px 30px -26px rgba(194, 65, 12, 0.9);
			}
			.zaher-popup-settings .zaher-popup-settings__list {
				display: grid;
				gap: 22px;
			}
			.zaher-popup-settings .zaher-popup-card {
				background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(252, 250, 246, 0.98));
				border: 1px solid var(--zaher-popup-border);
				border-radius: 28px;
				padding: 24px;
				box-shadow: var(--zaher-popup-shadow);
				transition: opacity 0.2s ease, transform 0.2s ease;
			}
			.zaher-popup-settings .zaher-popup-card.is-disabled {
				opacity: 0.72;
			}
			.zaher-popup-settings .zaher-popup-card__header {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				gap: 18px;
				margin-bottom: 20px;
			}
			.zaher-popup-settings .zaher-popup-card__actions {
				display: flex;
				align-items: center;
				gap: 14px;
			}
			.zaher-popup-settings .zaher-popup-card__title {
				margin: 0 0 6px;
				font-size: 22px;
				line-height: 1.2;
				color: var(--zaher-popup-text);
			}
			.zaher-popup-settings .zaher-popup-card__subtitle {
				margin: 0;
				max-width: 780px;
				color: var(--zaher-popup-muted);
				line-height: 1.55;
			}
			.zaher-popup-settings .zaher-popup-toggle {
				display: inline-flex;
				align-items: center;
				gap: 10px;
				cursor: pointer;
				user-select: none;
			}
			.zaher-popup-settings .zaher-popup-toggle input[type="hidden"] {
				display: none;
			}
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"] {
				position: absolute;
				opacity: 0;
				pointer-events: none;
			}
			.zaher-popup-settings .zaher-popup-toggle__track {
				position: relative;
				width: 48px;
				height: 28px;
				border-radius: 999px;
				background: #d1d5db;
				transition: background 0.2s ease;
			}
			.zaher-popup-settings .zaher-popup-toggle__track::after {
				content: "";
				position: absolute;
				top: 4px;
				left: 4px;
				width: 20px;
				height: 20px;
				border-radius: 50%;
				background: #ffffff;
				box-shadow: 0 4px 10px rgba(15, 23, 42, 0.22);
				transition: transform 0.2s ease;
			}
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"]:checked + .zaher-popup-toggle__track {
				background: linear-gradient(120deg, #ea580c, #db2777);
			}
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"]:checked + .zaher-popup-toggle__track::after {
				transform: translateX(20px);
			}
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"]:focus-visible + .zaher-popup-toggle__track {
				outline: 2px solid #1d4ed8;
				outline-offset: 2px;
			}
			.zaher-popup-settings .zaher-popup-toggle__label {
				font-weight: 700;
				color: var(--zaher-popup-text);
			}
			.zaher-popup-settings .zaher-popup-card .button-link-delete {
				padding: 0;
				color: #b42318;
			}
			.zaher-popup-settings .zaher-popup-card__layout {
				display: grid;
				grid-template-columns: minmax(0, 1.18fr) minmax(340px, 0.82fr);
				gap: 22px;
			}
			.zaher-popup-settings .zaher-popup-card__form,
			.zaher-popup-settings .zaher-popup-card__preview-pane {
				min-width: 0;
			}
			.zaher-popup-settings .zaher-popup-card__form {
				display: grid;
				gap: 18px;
			}
			.zaher-popup-settings .zaher-popup-card__section,
			.zaher-popup-settings .zaher-popup-card__preview-pane {
				padding: 20px;
				border-radius: 24px;
				border: 1px solid rgba(230, 221, 210, 0.9);
				background: var(--zaher-popup-surface);
			}
			.zaher-popup-settings .zaher-popup-card__section {
				box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.96);
			}
			.zaher-popup-settings .zaher-popup-card__section-header {
				margin-bottom: 16px;
			}
			.zaher-popup-settings .zaher-popup-card__section-header h3,
			.zaher-popup-settings .zaher-popup-card__preview-header h3 {
				margin: 0 0 6px;
				font-size: 18px;
				line-height: 1.25;
				color: var(--zaher-popup-text);
			}
			.zaher-popup-settings .zaher-popup-card__eyebrow {
				color: #9a3412;
			}
			.zaher-popup-settings .zaher-popup-card__grid {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 16px 18px;
			}
			.zaher-popup-settings .zaher-popup-card__grid--compact {
				grid-template-columns: repeat(2, minmax(220px, 1fr));
			}
			.zaher-popup-settings .zaher-popup-field {
				min-width: 0;
			}
			.zaher-popup-settings .zaher-popup-field.is-wide {
				grid-column: 1 / -1;
			}
			.zaher-popup-settings .zaher-popup-field label {
				display: block;
				margin-bottom: 7px;
				font-weight: 700;
				color: var(--zaher-popup-text);
			}
			.zaher-popup-settings .zaher-popup-field input,
			.zaher-popup-settings .zaher-popup-field select,
			.zaher-popup-settings .zaher-popup-field textarea {
				width: 100%;
				max-width: none;
				border-color: #d6d3d1;
				border-radius: 14px;
				padding: 9px 12px;
				box-shadow: none;
				background: #ffffff;
			}
			.zaher-popup-settings .zaher-popup-field textarea {
				min-height: 92px;
				resize: vertical;
			}
			.zaher-popup-settings .zaher-popup-field .wp-editor-wrap {
				border: 1px solid #d6d3d1;
				border-radius: 14px;
				overflow: hidden;
				background: #ffffff;
			}
			.zaher-popup-settings .zaher-popup-field .wp-editor-wrap .wp-editor-tools {
				padding: 0 12px;
				border-bottom: 1px solid #e7e5e4;
				background: #ffffff;
			}
			.zaher-popup-settings .zaher-popup-field .wp-editor-container,
			.zaher-popup-settings .zaher-popup-field .quicktags-toolbar,
			.zaher-popup-settings .zaher-popup-field .mce-top-part::before,
			.zaher-popup-settings .zaher-popup-field .mce-tinymce {
				border: 0;
				box-shadow: none;
			}
			.zaher-popup-settings .zaher-popup-field .wp-editor-area {
				min-height: 150px;
			}
			.zaher-popup-settings .zaher-popup-field .mce-toolbar-grp {
				border-bottom: 1px solid #e7e5e4;
			}
			.zaher-popup-settings .zaher-popup-field .mce-panel {
				border: 0;
				box-shadow: none;
			}
			.zaher-popup-settings .zaher-popup-field input:focus,
			.zaher-popup-settings .zaher-popup-field select:focus,
			.zaher-popup-settings .zaher-popup-field textarea:focus {
				border-color: #ea580c;
				box-shadow: 0 0 0 1px #ea580c;
			}
			.zaher-popup-settings .zaher-popup-field .description {
				margin: 8px 0 0;
				color: var(--zaher-popup-muted);
				line-height: 1.55;
			}
			.zaher-popup-settings .zaher-popup-card__section--manual-copy[hidden] {
				display: none;
			}
			.zaher-popup-settings .zaher-popup-card__preview-pane {
				background:
					radial-gradient(circle at top, rgba(236, 72, 153, 0.1), transparent 42%),
					linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(254, 247, 237, 0.98));
				display: grid;
				gap: 16px;
			}
			.zaher-popup-settings .zaher-popup-card__preview-header {
				display: flex;
				align-items: flex-start;
				justify-content: space-between;
				gap: 16px;
			}
			.zaher-popup-settings .zaher-popup-card__preview-note {
				display: inline-flex;
				align-items: center;
				padding: 7px 10px;
				border-radius: 999px;
				background: rgba(194, 65, 12, 0.08);
				color: #9a3412;
				font-size: 12px;
				font-weight: 700;
				letter-spacing: 0.04em;
				text-transform: uppercase;
			}
			.zaher-popup-settings .zaher-popup-card__preview {
				display: grid;
				gap: 14px;
			}
			.zaher-popup-settings .zaher-popup-preview__device {
				position: relative;
				padding: 26px 16px 16px;
				border-radius: 28px;
				background:
					radial-gradient(circle at top, rgba(251, 146, 60, 0.26), transparent 28%),
					linear-gradient(180deg, #1f2937, #0f172a 46%, #111827 100%);
				box-shadow: 0 24px 50px -34px rgba(15, 23, 42, 0.88);
				overflow: hidden;
			}
			.zaher-popup-settings .zaher-popup-preview__device::before {
				content: "";
				position: absolute;
				top: 10px;
				left: 50%;
				width: 96px;
				height: 10px;
				border-radius: 999px;
				background: rgba(255, 255, 255, 0.14);
				transform: translateX(-50%);
			}
			.zaher-popup-settings .zaher-popup-preview__backdrop {
				position: absolute;
				inset: 0;
				background: linear-gradient(180deg, rgba(15, 23, 42, 0.16), rgba(15, 23, 42, 0.54));
			}
			.zaher-popup-settings .zaher-popup-preview__modal {
				position: relative;
				z-index: 1;
				margin: 18px auto 0;
				max-width: 390px;
				padding: 24px 20px 18px;
				border-radius: 28px;
				background: #ffffff;
				border: 1px solid rgba(229, 231, 235, 0.96);
				box-shadow: 0 26px 60px -38px rgba(15, 23, 42, 0.52);
				text-align: center;
				overflow: hidden;
			}
			.zaher-popup-settings .zaher-popup-preview__accent {
				position: absolute;
				top: -1px;
				left: -1px;
				width: calc(100% + 2px);
				height: 4px;
				border-radius: 28px 28px 0 0;
				background: linear-gradient(90deg, #f97316 0%, #db2777 100%);
			}
			.zaher-popup-settings .zaher-popup-preview__close {
				position: absolute;
				top: 14px;
				right: 14px;
				width: 32px;
				height: 32px;
				border: 1px solid rgba(209, 213, 219, 0.96);
				border-radius: 999px;
				background: #ffffff;
				color: #9ca3af;
				font-size: 18px;
				line-height: 1;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}
			.zaher-popup-settings .zaher-popup-preview__badge {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				margin: 0 0 16px;
				padding: 8px 12px;
				border-radius: 999px;
				background: #fff4eb;
				color: #c2410c;
				font-size: 11px;
				font-weight: 700;
				letter-spacing: 0.16em;
				text-transform: uppercase;
			}
			.zaher-popup-settings .zaher-popup-preview__title {
				margin: 0;
				font-size: 31px;
				line-height: 1.06;
				letter-spacing: -0.04em;
				font-weight: 700;
				color: #111827;
			}
			.zaher-popup-settings .zaher-popup-preview__subtitle {
				color: #6b7280;
				line-height: 1.58;
				margin: 14px 0 0;
				font-size: 15px;
				display: grid;
				gap: 10px;
			}
			.zaher-popup-settings .zaher-popup-preview__subtitle p,
			.zaher-popup-settings .zaher-popup-preview__subtitle ul,
			.zaher-popup-settings .zaher-popup-preview__subtitle ol {
				margin: 0;
			}
			.zaher-popup-settings .zaher-popup-preview__subtitle strong {
				color: #111827;
			}
			.zaher-popup-settings .zaher-popup-preview__prices {
				display: grid;
				gap: 10px;
				margin-top: 18px;
				padding: 16px 14px;
				border-radius: 20px;
				background: #fafaf9;
				border: 1px solid rgba(229, 231, 235, 0.96);
			}
			.zaher-popup-settings .zaher-popup-preview__price-row {
				display: flex;
				align-items: baseline;
				justify-content: center;
				flex-wrap: wrap;
				gap: 9px;
			}
			.zaher-popup-settings .zaher-popup-preview__price-old {
				color: #9ca3af;
				font-size: 16px;
				text-decoration: line-through;
			}
			.zaher-popup-settings .zaher-popup-preview__price-old span {
				font-size: 0.82em;
			}
			.zaher-popup-settings .zaher-popup-preview__price-arrow {
				color: #c2410c;
				font-size: 18px;
			}
			.zaher-popup-settings .zaher-popup-preview__price-new {
				color: #111827;
				font-size: 34px;
				line-height: 1;
				font-weight: 700;
				letter-spacing: -0.03em;
			}
			.zaher-popup-settings .zaher-popup-preview__price-new span {
				font-size: 14px;
				font-weight: 500;
				color: #6b7280;
			}
			.zaher-popup-settings .zaher-popup-preview__renewal {
				margin: 0;
				color: #6b7280;
				font-size: 12px;
				line-height: 1.5;
			}
			.zaher-popup-settings .zaher-popup-preview__benefit {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				margin: 0 auto;
				padding: 7px 12px;
				border-radius: 999px;
				background: rgba(194, 65, 12, 0.08);
				color: #7c2d12;
				font-size: 12px;
				font-weight: 700;
				line-height: 1.45;
			}
			.zaher-popup-settings .zaher-popup-preview__urgency {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				gap: 8px;
				margin-top: 14px;
				color: #6b7280;
				font-size: 12px;
				font-weight: 600;
			}
			.zaher-popup-settings .zaher-popup-preview__urgency-dot {
				width: 9px;
				height: 9px;
				border-radius: 50%;
				background: #ea580c;
				box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.4);
				animation: zaherPopupPreviewPulse 1.8s ease-out infinite;
			}
			.zaher-popup-settings .zaher-popup-preview__timing {
				margin-top: 14px;
				color: #6b7280;
				font-size: 12px;
				line-height: 1.5;
			}
			.zaher-popup-settings .zaher-popup-preview__cta,
			.zaher-popup-settings .zaher-popup-preview__skip {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 100%;
				border-radius: 18px;
			}
			.zaher-popup-settings .zaher-popup-preview__cta {
				margin-top: 16px;
				padding: 14px 16px;
				border: 0;
				background: linear-gradient(120deg, #f97316, #ec4899);
				color: #ffffff;
				font-size: 14px;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 0.03em;
				box-shadow: 0 24px 40px -28px rgba(236, 72, 153, 0.9);
			}
			.zaher-popup-settings .zaher-popup-preview__skip {
				margin-top: 10px;
				padding: 0;
				border: 0;
				background: transparent;
				color: #9ca3af;
				font-size: 12px;
				text-decoration: underline;
				text-decoration-style: dashed;
				text-underline-offset: 3px;
			}
			.zaher-popup-settings .zaher-popup-preview__meta {
				display: grid;
				gap: 12px;
			}
			.zaher-popup-settings .zaher-popup-preview__meta-grid {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 10px;
			}
			.zaher-popup-settings .zaher-popup-preview__meta-item {
				padding: 12px 14px;
				border-radius: 16px;
				background: rgba(255, 255, 255, 0.82);
				border: 1px solid rgba(230, 221, 210, 0.9);
			}
			.zaher-popup-settings .zaher-popup-preview__meta-item span {
				display: block;
				margin-bottom: 5px;
				font-size: 11px;
				font-weight: 700;
				letter-spacing: 0.12em;
				text-transform: uppercase;
				color: #9ca3af;
			}
			.zaher-popup-settings .zaher-popup-preview__meta-item strong,
			.zaher-popup-settings .zaher-popup-preview__meta-item code {
				display: block;
				font-size: 13px;
				line-height: 1.55;
				color: #111827;
				word-break: break-word;
			}
			.zaher-popup-settings .zaher-popup-preview__meta-item code {
				font-family: SFMono-Regular, Consolas, Monaco, monospace;
			}
			.zaher-popup-settings .zaher-popup-preview__warning,
			.zaher-popup-settings .zaher-popup-preview__hint {
				padding: 12px 14px;
				border-radius: 16px;
				font-size: 13px;
				line-height: 1.55;
			}
			.zaher-popup-settings .zaher-popup-preview__warning {
				background: #fff7ed;
				border: 1px solid rgba(249, 115, 22, 0.25);
				color: #9a3412;
			}
			.zaher-popup-settings .zaher-popup-preview__hint {
				background: rgba(255, 255, 255, 0.82);
				border: 1px solid rgba(230, 221, 210, 0.9);
				color: var(--zaher-popup-muted);
			}
			.zaher-popup-settings .zaher-popup-settings__footer {
				margin-top: 22px;
				display: flex;
				gap: 12px;
				align-items: center;
			}
			.zaher-popup-settings .zaher-popup-settings__footer .button-primary {
				min-height: 46px;
				padding: 0 22px;
				border: 0;
				border-radius: 999px;
				background: linear-gradient(120deg, #c2410c, #db2777);
				box-shadow: 0 22px 42px -28px rgba(194, 65, 12, 0.78);
			}
			@keyframes zaherPopupPreviewPulse {
				0% {
					transform: scale(0.95);
					box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.34);
				}
				70% {
					transform: scale(1);
					box-shadow: 0 0 0 8px rgba(234, 88, 12, 0);
				}
				100% {
					transform: scale(0.95);
					box-shadow: 0 0 0 0 rgba(234, 88, 12, 0);
				}
			}
			@media (max-width: 960px) {
				.zaher-popup-settings {
					margin-right: 12px;
				}
				.zaher-popup-settings .zaher-popup-settings__hero,
				.zaher-popup-settings .zaher-popup-card__layout,
				.zaher-popup-settings .zaher-popup-settings__toolbar {
					grid-template-columns: 1fr;
				}
				.zaher-popup-settings .zaher-popup-settings__hero,
				.zaher-popup-settings .zaher-popup-settings__toolbar,
				.zaher-popup-settings .zaher-popup-card__header {
					align-items: flex-start;
				}
				.zaher-popup-settings .zaher-popup-settings__toolbar,
				.zaher-popup-settings .zaher-popup-card__header {
					flex-direction: column;
				}
				.zaher-popup-settings .zaher-popup-card__grid,
				.zaher-popup-settings .zaher-popup-card__grid--compact,
				.zaher-popup-settings .zaher-popup-preview__meta-grid,
				.zaher-popup-settings .zaher-popup-settings__stats {
					grid-template-columns: 1fr;
				}
			}
			@media (max-width: 640px) {
				.zaher-popup-settings .zaher-popup-settings__hero,
				.zaher-popup-settings .zaher-popup-card,
				.zaher-popup-settings .zaher-popup-settings__toolbar,
				.zaher-popup-settings .zaher-popup-card__section,
				.zaher-popup-settings .zaher-popup-card__preview-pane {
					padding: 18px;
					border-radius: 22px;
				}
				.zaher-popup-settings .zaher-popup-settings__hero h1 {
					font-size: 28px;
				}
				.zaher-popup-settings .zaher-popup-preview__modal {
					padding: 22px 16px 18px;
				}
				.zaher-popup-settings .zaher-popup-preview__title {
					font-size: 27px;
				}
			}
		</style>

		<div class="zaher-popup-settings__hero">
			<div>
				<p class="zaher-popup-settings__eyebrow">MemberPress admin</p>
				<h1>Checkout Popup</h1>
				<p>Svaki popup povezuje jedan checkout s ciljanim planom i opcionalnim kuponom. Cijene, ušteda i CTA URL i dalje se računaju automatski, a naslov i tekst popupa sada uređuješ direktno po popupu.</p>
			</div>
			<div class="zaher-popup-settings__stats">
				<div class="zaher-popup-settings__stat">
					<strong data-popup-total-count><?php echo esc_html( count( $rows ) ); ?></strong>
					<span>Ukupno popupova</span>
				</div>
				<div class="zaher-popup-settings__stat">
					<strong data-popup-active-count><?php echo esc_html( count( array_filter( $rows, static function( $row ) { return ! isset( $row['enabled'] ) || ! empty( $row['enabled'] ); } ) ) ); ?></strong>
					<span>Aktivnih popupova</span>
				</div>
			</div>
		</div>

		<?php settings_errors( 'zaher_checkout_popups' ); ?>

		<form action="options.php" method="post" id="zaher-checkout-popup-form">
			<?php settings_fields( 'zaher_checkout_popup_settings' ); ?>
			<input type="hidden" name="zaher_checkout_popups[_present]" value="1" />

			<div class="zaher-popup-settings__toolbar">
					<p>Možeš imati više popupova, ali samo jedan po izvornom checkoutu. Cijene i CTA URL se automatski prilagođavaju odabranoj ciljanoj pretplati i kuponu, dok copy uređuješ ručno.</p>
				<button type="button" class="button" id="zaher-add-popup">Dodaj popup</button>
			</div>

			<div class="zaher-popup-settings__list" id="zaher-popup-settings-list">
				<?php foreach ( $rows as $index => $popup ) : ?>
					<?php zaher_render_checkout_popup_row( $index, $popup, $source_products, $target_products, $coupon_choices ); ?>
				<?php endforeach; ?>
			</div>

			<div class="zaher-popup-settings__footer">
				<?php submit_button( 'Spremi postavke', 'primary', 'submit', false ); ?>
			</div>
		</form>

		<template id="zaher-popup-row-template">
			<?php zaher_render_checkout_popup_row( '__INDEX__', array(), $source_products, $target_products, $coupon_choices ); ?>
		</template>

		<script>
		(function() {
			const data = <?php echo wp_json_encode( $admin_data ); ?>;
			const list = document.getElementById('zaher-popup-settings-list');
			const addButton = document.getElementById('zaher-add-popup');
			const template = document.getElementById('zaher-popup-row-template');
			const totalCount = document.querySelector('[data-popup-total-count]');
			const activeCount = document.querySelector('[data-popup-active-count]');
			let editorInitTimer = null;
			let editorInitAttempts = 0;
			let nextIndex = <?php echo (int) count( $rows ); ?>;

			if (!list || !addButton || !template) {
				return;
			}

			function getProduct(collection, value) {
				return collection[String(value)] || null;
			}

			function getPeriodUnits(product) {
				if (!product || product.isOneTime) {
					return null;
				}

				if (product.periodType === 'months') {
					return { group: 'months', value: Number(product.period || 0) };
				}

				if (product.periodType === 'years') {
					return { group: 'months', value: Number(product.period || 0) * 12 };
				}

				if (product.periodType === 'weeks') {
					return { group: 'days', value: Number(product.period || 0) * 7 };
				}

				if (product.periodType === 'days') {
					return { group: 'days', value: Number(product.period || 0) };
				}

				return null;
			}

			function getPeriodRatio(source, target) {
				const sourceUnits = getPeriodUnits(source);
				const targetUnits = getPeriodUnits(target);

				if (!sourceUnits || !targetUnits || sourceUnits.group !== targetUnits.group || sourceUnits.value <= 0) {
					return 0;
				}

				const ratio = targetUnits.value / sourceUnits.value;

				if (!Number.isInteger(ratio) || ratio < 1) {
					return 0;
				}

				return ratio;
			}

			function escapeHtml(value) {
				return String(value || '')
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;');
			}

			function stripTags(value) {
				const element = document.createElement('div');
				element.innerHTML = String(value || '');
				return element.textContent || element.innerText || '';
			}

			function sanitizePreviewHtml(value) {
				const allowedTags = new Set(['STRONG', 'BR', 'EM', 'B', 'I', 'P', 'UL', 'OL', 'LI']);
				const templateElement = document.createElement('template');

				templateElement.innerHTML = String(value || '');

				(function walk(node) {
					Array.from(node.childNodes).forEach(function(child) {
						if (child.nodeType === Node.ELEMENT_NODE) {
							if (!allowedTags.has(child.tagName)) {
								child.replaceWith(document.createTextNode(child.textContent || ''));
								return;
							}

							Array.from(child.attributes).forEach(function(attribute) {
								child.removeAttribute(attribute.name);
							});
							walk(child);
							return;
						}

						if (child.nodeType !== Node.TEXT_NODE) {
							child.remove();
						}
					});
				})(templateElement.content);

				return templateElement.innerHTML;
			}

			function replaceTokens(value, replacements) {
				let output = String(value || '');

				Object.keys(replacements).forEach(function(token) {
					output = output.split(token).join(replacements[token]);
				});

				return output;
			}

			function applyRichText(value, replacements) {
				return sanitizePreviewHtml(
					replaceTokens(
						String(value || '').replace(/\r\n?/g, '\n').replace(/\n/g, '<br>'),
						replacements
					)
				);
			}

			function applyPlainText(value, replacements) {
				return stripTags(replaceTokens(value, replacements)).trim();
			}

			function normalizeTitleHtml(value) {
				return String(value || '')
					.replace(/<\s*\/p>\s*<\s*p[^>]*>\s*/gi, '<br>')
					.replace(/<\s*p[^>]*>\s*/gi, '')
					.replace(/\s*<\s*\/p>\s*/gi, '')
					.trim();
			}

			function getFieldValue(field) {
				if (!field) {
					return '';
				}

				if (field.hasAttribute('data-popup-rich-editor') && window.tinymce && field.id) {
					const editor = window.tinymce.get(field.id);

					if (
						editor &&
						typeof editor.getContent === 'function' &&
						(typeof editor.isHidden !== 'function' || !editor.isHidden())
					) {
						return editor.getContent();
					}
				}

				return field.value || '';
			}

			function formatMoney(amount) {
				const numericAmount = Number(amount || 0);
				const formatted = numericAmount.toLocaleString('hr-HR', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2,
				});

				return data.currency.after ? formatted + ' ' + data.currency.symbol : data.currency.symbol + formatted;
			}

			function getPlanLabel(product, grammaticalCase) {
				const fallback = {
					accusative: 'odabranu pretplatu',
					locative_bare: 'odabranoj pretplati',
					locative: 'na odabranoj pretplati',
					genitive: 'odabrane pretplate',
					nominative: 'odabrana pretplata',
				};

				if (!product) {
					return fallback[grammaticalCase] || fallback.nominative;
				}

				const labels = {
					'months:1': {
						nominative: 'mjesečna pretplata',
						accusative: 'mjesečnu pretplatu',
						locative_bare: 'mjesečnoj pretplati',
						locative: 'na mjesečnoj pretplati',
						genitive: 'mjesečne pretplate',
					},
					'months:3': {
						nominative: 'tromjesečna pretplata',
						accusative: 'tromjesečnu pretplatu',
						locative_bare: 'tromjesečnoj pretplati',
						locative: 'na tromjesečnoj pretplati',
						genitive: 'tromjesečne pretplate',
					},
					'months:6': {
						nominative: 'polugodišnja pretplata',
						accusative: 'polugodišnju pretplatu',
						locative_bare: 'polugodišnjoj pretplati',
						locative: 'na polugodišnjoj pretplati',
						genitive: 'polugodišnje pretplate',
					},
					'months:12': {
						nominative: 'godišnja pretplata',
						accusative: 'godišnju pretplatu',
						locative_bare: 'godišnjoj pretplati',
						locative: 'na godišnjoj pretplati',
						genitive: 'godišnje pretplate',
					},
					'years:1': {
						nominative: 'godišnja pretplata',
						accusative: 'godišnju pretplatu',
						locative_bare: 'godišnjoj pretplati',
						locative: 'na godišnjoj pretplati',
						genitive: 'godišnje pretplate',
					},
				};
				const productKey = String(product.periodType || '') + ':' + String(Number(product.period || 0));

				if (labels[productKey] && labels[productKey][grammaticalCase]) {
					return labels[productKey][grammaticalCase];
				}

				return fallback[grammaticalCase] || fallback.nominative;
			}

			function formatProductAmountText(product, amount) {
				if (!product) {
					return '';
				}

				const amountText = formatMoney(amount);

				if (product.isOneTime) {
					return amountText;
				}

				if (Number(product.period || 0) <= 1) {
					return amountText + ' / ' + product.shortPeriodLabel;
				}

				return amountText + ' / ' + product.period + ' ' + product.shortPeriodLabel;
			}

			function isCouponValidForTarget(coupon, target) {
				if (!coupon || !target) {
					return false;
				}

				if (coupon.appliesToAllProducts) {
					return true;
				}

				return Array.isArray(coupon.validProductIds) && coupon.validProductIds.indexOf(Number(target.id)) !== -1;
			}

			function applyDiscount(amount, discountType, discountAmount) {
				let adjustedAmount = Number(amount || 0);

				if (discountType === 'percent') {
					adjustedAmount = adjustedAmount * (1 - (Number(discountAmount || 0) / 100));
				} else {
					adjustedAmount = adjustedAmount - Number(discountAmount || 0);
				}

				return Math.max(0, adjustedAmount);
			}

			function getPricingData(target, coupon) {
				const empty = {
					baseAmount: 0,
					baseText: '',
					displayAmount: 0,
					displayText: '',
					comparisonAmount: 0,
					comparisonHtml: '',
					offerSummaryHtml: '',
					renewalText: '',
					couponMode: '',
					usesTrialOverride: false,
				};

				if (!target) {
					return empty;
				}

				const baseAmount = Number(target.price || 0);
				const baseText = formatProductAmountText(target, baseAmount);
				const pricing = Object.assign({}, empty, {
					baseAmount: baseAmount,
					baseText: baseText,
					displayAmount: baseAmount,
					displayText: baseText,
					comparisonAmount: baseAmount,
					comparisonHtml: '<strong>' + escapeHtml(baseText) + '</strong>',
					offerSummaryHtml: 'Na ovom checkoutu danas plaćaš <strong>' + escapeHtml(baseText) + '</strong>.',
					renewalText: baseText,
				});

				if (!coupon || !isCouponValidForTarget(coupon, target)) {
					return pricing;
				}

				pricing.couponMode = String(coupon.discountMode || '');

				if (pricing.couponMode === 'trial-override') {
					pricing.usesTrialOverride = true;
					pricing.offerSummaryHtml = 'Na ovom checkoutu vrijedi posebna trial ponuda za prvi obračun.';
					return pricing;
				}

				if (pricing.couponMode === 'first-payment') {
					const firstPaymentAmount = applyDiscount(
						baseAmount,
						coupon.firstPaymentDiscountType,
						coupon.firstPaymentDiscountAmount
					);
					const firstPaymentText = formatProductAmountText(target, firstPaymentAmount);

					pricing.displayAmount = firstPaymentAmount;
					pricing.displayText = firstPaymentText;
					pricing.comparisonAmount = firstPaymentAmount;
					pricing.comparisonHtml = '<strong>' + escapeHtml(firstPaymentText) + '</strong>, poslije <strong>' + escapeHtml(baseText) + '</strong>';
					pricing.offerSummaryHtml = 'Prvi obračun danas iznosi <strong>' + escapeHtml(firstPaymentText) + '</strong> umjesto redovne cijene od <strong>' + escapeHtml(baseText) + '</strong>.';
					pricing.renewalText = baseText;

					return pricing;
				}

				const discountedAmount = applyDiscount(baseAmount, coupon.discountType, coupon.discountAmount);
				const discountedText = formatProductAmountText(target, discountedAmount);

				pricing.displayAmount = discountedAmount;
				pricing.displayText = discountedText;
				pricing.comparisonAmount = discountedAmount;

				if (discountedText === baseText) {
					return pricing;
				}

				pricing.comparisonHtml = '<strong>' + escapeHtml(discountedText) + '</strong> umjesto <strong>' + escapeHtml(baseText) + '</strong>';
				pricing.offerSummaryHtml = 'Na ovom checkoutu danas plaćaš <strong>' + escapeHtml(discountedText) + '</strong> umjesto redovne cijene od <strong>' + escapeHtml(baseText) + '</strong>.';

				return pricing;
			}

			function getTargetUrl(target, coupon) {
				if (!target) {
					return '';
				}

				if (!coupon || !isCouponValidForTarget(coupon, target)) {
					return target.url;
				}

				return target.url + (target.url.indexOf('?') === -1 ? '?' : '&') + 'coupon=' + encodeURIComponent(coupon.code);
			}

			function getReferencePriceAmount(source, target) {
				if (!target) {
					return 0;
				}

				if (source) {
					const ratio = getPeriodRatio(source, target);

					if (ratio > 1) {
						return Number(source.price || 0) * ratio;
					}
				}

				return Number(target.price || 0);
			}

			function getSavingsText(source, target, coupon) {
				if (!target) {
					return '';
				}

				const pricing = getPricingData(target, coupon);
				const savingsAmount = Math.max(0, getReferencePriceAmount(source, target) - Number(pricing.comparisonAmount || 0));

				if (savingsAmount <= 0) {
					return '';
				}

				return formatMoney(savingsAmount);
			}

			function getPriceComparisonHtml(target, coupon) {
				return getPricingData(target, coupon).comparisonHtml || '';
			}

			function getOfferSummaryHtml(target, coupon) {
				return getPricingData(target, coupon).offerSummaryHtml || '';
			}

			function getValueSentenceHtml(source, target, coupon) {
				if (!target) {
					return '';
				}

				const pricing = getPricingData(target, coupon);
				const savingsText = getSavingsText(source, target, coupon);
				let sentence = '';

				if (pricing.couponMode === 'first-payment') {
					sentence = 'Na ovom checkoutu prvi obračun plaćaš <strong>' + escapeHtml(pricing.displayText) + '</strong> umjesto <strong>' + escapeHtml(pricing.baseText) + '</strong>';
				} else if (pricing.couponMode === 'trial-override') {
					sentence = 'Na ovom checkoutu vrijedi posebna trial ponuda za prvi obračun';
				} else if (pricing.displayText !== pricing.baseText) {
					sentence = 'Na ovom checkoutu plaćaš <strong>' + escapeHtml(pricing.displayText) + '</strong> umjesto <strong>' + escapeHtml(pricing.baseText) + '</strong>';
				} else {
					sentence = 'Na ovom checkoutu odmah prelaziš na <strong>' + escapeHtml(getPlanLabel(target, 'accusative')) + '</strong>';
				}

				if (savingsText) {
					sentence += ', a kroz isti period štediš <strong>' + escapeHtml(savingsText) + '</strong> u odnosu na ostanak ' + escapeHtml(getPlanLabel(source, 'locative'));
				}

				return sentence + '.';
			}

			function getSavingsSentenceHtml(source, target, coupon) {
				const savingsText = getSavingsText(source, target, coupon);

				if (!savingsText) {
					return '';
				}

				return ' U odnosu na ostanak ' + escapeHtml(getPlanLabel(source, 'locative')) + ' kroz isti period štediš <strong>' + escapeHtml(savingsText) + '</strong>.';
			}

			function getVsCurrentPlanBenefitHtml(source, target, coupon) {
				const savingsText = getSavingsText(source, target, coupon);

				if (!savingsText) {
					return 'U odnosu na ostanak ' + escapeHtml(getPlanLabel(source, 'locative')) + ' kroz isti period, ovo je isplativiji start.';
				}

				return 'U odnosu na ostanak ' + escapeHtml(getPlanLabel(source, 'locative')) + ' kroz isti period štediš <strong>' + escapeHtml(savingsText) + '</strong>.';
			}

			function getPriceBoxData(source, target, coupon) {
				const pricing = getPricingData(target, coupon);
				const savingsText = getSavingsText(source, target, coupon);
				const priceBox = {
					oldPrice: '',
					newPrice: pricing.displayText || '',
					renewalNote: '',
					benefitPrimary: '',
				};

				if (pricing.displayText && pricing.displayText !== pricing.baseText) {
					priceBox.oldPrice = pricing.baseText || '';
				}

				if (pricing.couponMode === 'first-payment' || pricing.couponMode === 'trial-override') {
					if (pricing.renewalText) {
						priceBox.renewalNote = 'Popust vrijedi za prvi obračun. Nakon toga ' + pricing.renewalText + '.';
					}
				}

				if (savingsText) {
					priceBox.benefitPrimary = 'Štediš ' + savingsText + ' kroz isti period.';
				}

				return priceBox;
			}

				function getTemplateSource(row) {
					const defaults = data.templates[data.defaultTemplateKey] || {};
					const source = {
						badgeText: defaults.badgeText || '',
						titleHtml: defaults.titleHtml || '',
						subtitleHtml: defaults.subtitleHtml || '',
						ctaLabel: defaults.ctaLabel || '',
						skipLabel: defaults.skipLabel || '',
					};

				if (!row) {
					return source;
				}

				row.querySelectorAll('[data-popup-custom-input]').forEach(function(field) {
					const key = field.getAttribute('data-popup-custom-input');

					if (!key) {
						return;
					}

					if (key === 'title_html') {
						source.titleHtml = getFieldValue(field);
					}

						if (key === 'subtitle_html') {
							source.subtitleHtml = getFieldValue(field);
						}
					});

					return source;
			}

			function renderPriceMarkup(value, className) {
				if (!value) {
					return '';
				}

				const parts = String(value).split(' / ');

				if (parts.length === 2) {
					return '<span class="' + className + '">' + escapeHtml(parts[0]) + ' <span>/ ' + escapeHtml(parts[1]) + '</span></span>';
				}

				return '<span class="' + className + '">' + escapeHtml(value) + '</span>';
			}

			function renderMetaItem(label, value, useCode) {
				const contentTag = useCode ? 'code' : 'strong';
				const safeValue = useCode ? escapeHtml(value) : escapeHtml(value);

				return '<div class="zaher-popup-preview__meta-item"><span>' + escapeHtml(label) + '</span><' + contentTag + '>' + safeValue + '</' + contentTag + '></div>';
			}

			function getTemplateContent(row, source, target, coupon) {
				const templateSource = getTemplateSource(row);
				const replacements = {
					'{{target_title}}': escapeHtml(target ? target.title : ''),
					'{{target_plan_nominative}}': escapeHtml(getPlanLabel(target, 'nominative')),
					'{{target_plan_accusative}}': escapeHtml(getPlanLabel(target, 'accusative')),
					'{{target_plan_genitive}}': escapeHtml(getPlanLabel(target, 'genitive')),
					'{{source_plan_locative_bare}}': escapeHtml(getPlanLabel(source, 'locative_bare')),
					'{{source_plan_locative}}': escapeHtml(getPlanLabel(source, 'locative')),
					'{{value_sentence_html}}': getValueSentenceHtml(source, target, coupon),
					'{{price_comparison_html}}': getPriceComparisonHtml(target, coupon),
					'{{offer_summary_html}}': getOfferSummaryHtml(target, coupon),
					'{{savings_text}}': escapeHtml(getSavingsText(source, target, coupon)),
					'{{savings_sentence_html}}': getSavingsSentenceHtml(source, target, coupon),
					'{{vs_current_plan_benefit_html}}': getVsCurrentPlanBenefitHtml(source, target, coupon),
					'{{savings_suffix}}': '',
				};

					return {
						badgeText: applyPlainText(templateSource.badgeText, replacements),
						titleHtml: normalizeTitleHtml(applyRichText(templateSource.titleHtml, replacements)),
						subtitleHtml: applyRichText(templateSource.subtitleHtml, replacements),
						ctaLabel: applyPlainText(templateSource.ctaLabel, replacements),
						skipLabel: applyPlainText(templateSource.skipLabel, replacements),
					};
				}

			function buildPreview(row, state) {
				const previewParts = [];
				const warnings = [];
				const priceBox = getPriceBoxData(state.source, state.target, state.validCoupon);
				const content = getTemplateContent(row, state.source, state.target, state.validCoupon);

				if (state.coupon && !state.couponValid) {
					warnings.push('Odabrani kupon nije valjan za ciljanu pretplatu pa se u previewu ignorira.');
				}

				if (state.pricing.usesTrialOverride) {
					warnings.push('Trial override kupon može imati drugačiji prvi obračun od ovog pregleda. Backend će i dalje obračunati stvarnu cijenu na checkoutu.');
				}

				previewParts.push('<div class="zaher-popup-preview__device">');
				previewParts.push('<div class="zaher-popup-preview__backdrop" aria-hidden="true"></div>');
				previewParts.push('<div class="zaher-popup-preview__modal">');
				previewParts.push('<div class="zaher-popup-preview__accent" aria-hidden="true"></div>');
				previewParts.push('<button type="button" class="zaher-popup-preview__close" tabindex="-1" aria-hidden="true">&times;</button>');

					if (content.badgeText) {
						previewParts.push('<p class="zaher-popup-preview__badge">' + escapeHtml(content.badgeText) + '</p>');
					}

					previewParts.push('<h4 class="zaher-popup-preview__title">' + (content.titleHtml || 'Ovdje će se prikazati naslov popupa') + '</h4>');
					previewParts.push('<div class="zaher-popup-preview__subtitle">' + (content.subtitleHtml || 'Odaberi ciljanu pretplatu za pregled popup sadržaja.') + '</div>');

				previewParts.push('<div class="zaher-popup-preview__prices">');
				previewParts.push('<div class="zaher-popup-preview__price-row">');

				if (priceBox.oldPrice) {
					previewParts.push(renderPriceMarkup(priceBox.oldPrice, 'zaher-popup-preview__price-old'));
					previewParts.push('<span class="zaher-popup-preview__price-arrow" aria-hidden="true">&rarr;</span>');
				}

				previewParts.push(renderPriceMarkup(priceBox.newPrice || 'Odaberi ciljanu pretplatu.', 'zaher-popup-preview__price-new'));
				previewParts.push('</div>');

				if (priceBox.renewalNote) {
					previewParts.push('<p class="zaher-popup-preview__renewal">' + escapeHtml(priceBox.renewalNote) + '</p>');
				}

				if (priceBox.benefitPrimary) {
					previewParts.push('<p class="zaher-popup-preview__benefit">' + escapeHtml(priceBox.benefitPrimary) + '</p>');
				}

				previewParts.push('</div>');
				previewParts.push('<div class="zaher-popup-preview__urgency"><span class="zaher-popup-preview__urgency-dot" aria-hidden="true"></span><span>Ponuda vrijedi samo na ovom checkoutu</span></div>');
				previewParts.push('<button type="button" class="zaher-popup-preview__cta" tabindex="-1">' + escapeHtml(content.ctaLabel || 'Da, želim ovu ponudu') + '</button>');
				previewParts.push('<button type="button" class="zaher-popup-preview__skip" tabindex="-1">' + escapeHtml(content.skipLabel || 'Ne, ostajem pri odabranoj pretplati') + '</button>');
				previewParts.push('</div>');
				previewParts.push('</div>');

				previewParts.push('<div class="zaher-popup-preview__meta">');
				previewParts.push('<div class="zaher-popup-preview__meta-grid">');
				previewParts.push(renderMetaItem('Status', state.isEnabled ? 'Uključen' : 'Isključen', false));
				previewParts.push(renderMetaItem('Danas plaćaš', state.pricing.displayText || 'Odaberi ciljanu pretplatu.', false));
				previewParts.push(renderMetaItem('CTA URL', state.ctaUrl || 'Odaberi ciljanu pretplatu.', true));
				previewParts.push('</div>');

				if (warnings.length) {
					warnings.forEach(function(message) {
						previewParts.push('<div class="zaher-popup-preview__warning">' + escapeHtml(message) + '</div>');
					});
				}

				previewParts.push('</div>');

				return previewParts.join('');
			}

			function initializeRichEditor(field) {
				if (!field || !field.id || !field.hasAttribute('data-popup-rich-editor')) {
					return;
				}

				if (
					!window.wp ||
					!window.wp.editor ||
					typeof window.wp.editor.initialize !== 'function' ||
					field.getAttribute('data-popup-editor-initialized') === '1'
				) {
					return;
				}

				field.setAttribute('data-popup-editor-initialized', '1');

				window.wp.editor.initialize(field.id, {
					mediaButtons: false,
					quicktags: true,
					tinymce: {
						wpautop: true,
						menubar: false,
						branding: false,
						statusbar: false,
						resize: true,
						toolbar1: 'bold italic bullist numlist blockquote link unlink undo redo',
						toolbar2: '',
						setup: function(editor) {
							const refreshPreview = function() {
								editor.save();
								updateAllRows();
							};

							editor.on('init', refreshPreview);
							editor.on('change keyup NodeChange SetContent Undo Redo', refreshPreview);
						},
					},
				});
			}

			function initializeRowEditors(row) {
				row.querySelectorAll('[data-popup-rich-editor]').forEach(function(field) {
					initializeRichEditor(field);
				});
			}

			function scheduleEditorInitialization() {
				if (editorInitTimer) {
					window.clearTimeout(editorInitTimer);
				}

				editorInitTimer = window.setTimeout(function() {
					editorInitTimer = null;

					if (!window.wp || !window.wp.editor || typeof window.wp.editor.initialize !== 'function') {
						editorInitAttempts += 1;

						if (editorInitAttempts < 40) {
							scheduleEditorInitialization();
						}

						scheduleEditorInitialization();
						return;
					}

					editorInitAttempts = 0;

					Array.from(list.querySelectorAll('[data-popup-row]')).forEach(function(row) {
						initializeRowEditors(row);
					});
				}, 250);
			}

			function destroyRowEditors(row) {
				if (!window.wp || !window.wp.editor || typeof window.wp.editor.remove !== 'function') {
					return;
				}

				row.querySelectorAll('[data-popup-rich-editor]').forEach(function(field) {
					if (!field.id || field.getAttribute('data-popup-editor-initialized') !== '1') {
						return;
					}

					try {
						window.wp.editor.remove(field.id);
					} catch (error) {
						// Ignore teardown errors from partially initialized editors.
					}

					field.removeAttribute('data-popup-editor-initialized');
				});
			}

			function updateStats() {
				const rows = Array.from(list.querySelectorAll('[data-popup-row]'));
				const activeRows = rows.filter(function(row) {
					const enabledField = row.querySelector('[data-popup-enabled]');
					return !enabledField || enabledField.checked;
				});

				if (totalCount) {
					totalCount.textContent = String(rows.length);
				}

				if (activeCount) {
					activeCount.textContent = String(activeRows.length);
				}
			}

			function updateRow(row, rowIndex) {
				const sourceSelect = row.querySelector('[data-popup-source]');
				const targetSelect = row.querySelector('[data-popup-target]');
				const couponSelect = row.querySelector('[data-popup-coupon]');
				const enabledField = row.querySelector('[data-popup-enabled]');
				const enabledLabel = row.querySelector('[data-popup-enabled-label]');
				const title = row.querySelector('[data-popup-title]');
				const subtitle = row.querySelector('[data-popup-subtitle]');
				const preview = row.querySelector('[data-popup-preview]');
				const isEnabled = !enabledField || enabledField.checked;
				const source = sourceSelect ? getProduct(data.sourceProducts, sourceSelect.value) : null;
				const target = targetSelect ? getProduct(data.targetProducts, targetSelect.value) : null;
				const coupon = couponSelect && couponSelect.value ? data.coupons[couponSelect.value] || null : null;
				const couponValid = isCouponValidForTarget(coupon, target);
				const validCoupon = couponValid ? coupon : null;
				const ctaUrl = getTargetUrl(target, validCoupon);
				const pricing = getPricingData(target, validCoupon);
				const state = {
					source: source,
					target: target,
					coupon: coupon,
					couponValid: couponValid,
					validCoupon: validCoupon,
					ctaUrl: ctaUrl,
					pricing: pricing,
					isEnabled: isEnabled,
				};

				if (title) {
					title.textContent = 'Popup #' + rowIndex;
				}

				row.classList.toggle('is-disabled', !isEnabled);

				if (enabledLabel) {
					enabledLabel.textContent = isEnabled ? 'Uključen' : 'Isključen';
				}

				if (subtitle) {
					if (source && target) {
						subtitle.textContent = source.title + ' -> ' + target.title + (isEnabled ? '' : ' | Popup ugašen');
					} else if (source) {
						subtitle.textContent = source.title + ' -> odaberi ciljanu pretplatu' + (isEnabled ? '' : ' | Popup ugašen');
					} else {
						subtitle.textContent = 'Odaberi checkout na kojem se popup prikazuje i pretplatu na koju vodi CTA.' + (isEnabled ? '' : ' Popup je trenutno ugašen.');
					}
				}

				if (preview) {
					preview.innerHTML = buildPreview(row, state);
				}
			}

			function updateAllRows() {
				Array.from(list.querySelectorAll('[data-popup-row]')).forEach(function(row, index) {
					updateRow(row, index + 1);
				});
				updateStats();
			}

			function bindRow(row) {
				row.querySelectorAll('select, input, textarea').forEach(function(field) {
					field.addEventListener('change', updateAllRows);
					field.addEventListener('input', updateAllRows);
				});

				if (window.wp && window.wp.editor && typeof window.wp.editor.initialize === 'function') {
					initializeRowEditors(row);
				} else {
					scheduleEditorInitialization();
				}

				const removeButton = row.querySelector('[data-remove-popup]');
				if (removeButton) {
					removeButton.addEventListener('click', function() {
						destroyRowEditors(row);
						row.remove();
						updateAllRows();
					});
				}
			}

			addButton.addEventListener('click', function() {
				const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
				const wrapper = document.createElement('div');
				wrapper.innerHTML = html.trim();
				const row = wrapper.firstElementChild;

				nextIndex += 1;

				if (!row) {
					return;
				}

				list.appendChild(row);
				bindRow(row);
				updateAllRows();
			});

			Array.from(list.querySelectorAll('[data-popup-row]')).forEach(bindRow);
			updateAllRows();
			scheduleEditorInitialization();
		})();
		</script>
	</div>
	<?php
}
