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
	$defaults      = zaher_get_checkout_popup_defaults();
	$templates     = zaher_get_checkout_popup_templates();
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

		$template_key      = isset( $row['template_key'] ) ? sanitize_key( $row['template_key'] ) : $default_key;
		$source_product_id = isset( $row['source_product_id'] ) ? absint( $row['source_product_id'] ) : 0;
		$target_product_id = isset( $row['target_product_id'] ) ? absint( $row['target_product_id'] ) : 0;
		$coupon_code       = isset( $row['coupon_code'] ) ? sanitize_text_field( $row['coupon_code'] ) : '';
		$timer_minutes     = isset( $row['timer_minutes'] ) && '' !== $row['timer_minutes'] ? max( 1, absint( $row['timer_minutes'] ) ) : $defaults['timer_minutes'];
		$delay_seconds     = isset( $row['delay_seconds'] ) && '' !== $row['delay_seconds'] ? max( 0, absint( $row['delay_seconds'] ) ) : $defaults['delay_seconds'];
		$enabled           = ! isset( $row['enabled'] ) || '0' !== (string) $row['enabled'];

		if ( ! isset( $templates[ $template_key ] ) ) {
			$template_key = $default_key;
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

		$sanitized[] = array(
			'template_key'      => $template_key,
			'source_product_id' => $source_product_id,
			'target_product_id' => $target_product_id,
			'coupon_code'       => $coupon_code,
			'timer_minutes'     => $timer_minutes,
			'delay_seconds'     => $delay_seconds,
			'enabled'           => $enabled ? 1 : 0,
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

function zaher_render_checkout_popup_row( $index, $popup, $source_products, $target_products, $coupon_choices, $template_choices ) {
	$defaults          = zaher_get_checkout_popup_defaults();
	$template_key      = isset( $popup['template_key'] ) ? sanitize_key( $popup['template_key'] ) : zaher_get_checkout_popup_default_template_key();
	$source_product_id = isset( $popup['source_product_id'] ) ? absint( $popup['source_product_id'] ) : 0;
	$target_product_id = isset( $popup['target_product_id'] ) ? absint( $popup['target_product_id'] ) : 0;
	$coupon_code       = isset( $popup['coupon_code'] ) ? sanitize_text_field( $popup['coupon_code'] ) : '';
	$timer_minutes     = isset( $popup['timer_minutes'] ) && '' !== $popup['timer_minutes'] ? max( 1, absint( $popup['timer_minutes'] ) ) : $defaults['timer_minutes'];
	$delay_seconds     = isset( $popup['delay_seconds'] ) && '' !== $popup['delay_seconds'] ? max( 0, absint( $popup['delay_seconds'] ) ) : $defaults['delay_seconds'];
	$enabled           = ! isset( $popup['enabled'] ) || ! empty( $popup['enabled'] );
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
				<label for="zaher-popup-template-<?php echo esc_attr( $index ); ?>">Template</label>
				<select id="zaher-popup-template-<?php echo esc_attr( $index ); ?>" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][template_key]" data-popup-template>
					<?php zaher_render_checkout_popup_select_options( $template_choices, $template_key, 'Odaberi template' ); ?>
				</select>
				<p class="description">Odaberi unaprijed definirani copy i strukturu popupa.</p>
			</div>

			<div class="zaher-popup-field">
				<label for="zaher-popup-coupon-<?php echo esc_attr( $index ); ?>">Kupon</label>
				<select id="zaher-popup-coupon-<?php echo esc_attr( $index ); ?>" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][coupon_code]" data-popup-coupon>
					<?php zaher_render_checkout_popup_select_options( $coupon_choices, $coupon_code, 'Bez kupona' ); ?>
				</select>
				<p class="description">Ako je kupon valjan za ciljanu pretplatu, nova cijena i CTA URL računaju se automatski preko njega.</p>
			</div>

			<div class="zaher-popup-field">
				<label for="zaher-popup-timer-<?php echo esc_attr( $index ); ?>">Trajanje odbrojavanja (minuta)</label>
				<input id="zaher-popup-timer-<?php echo esc_attr( $index ); ?>" type="number" min="1" step="1" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][timer_minutes]" value="<?php echo esc_attr( $timer_minutes ); ?>" data-popup-timer />
				<p class="description">Default je podešen konzervativno za checkout UX: dovoljno urgentno, bez agresivnog pritiska.</p>
			</div>

			<div class="zaher-popup-field">
				<label for="zaher-popup-delay-<?php echo esc_attr( $index ); ?>">Kašnjenje prikaza (sekunde)</label>
				<input id="zaher-popup-delay-<?php echo esc_attr( $index ); ?>" type="number" min="0" step="1" name="zaher_checkout_popups[<?php echo esc_attr( $index ); ?>][delay_seconds]" value="<?php echo esc_attr( $delay_seconds ); ?>" data-popup-delay />
				<p class="description">Default je lagani odmak nakon učitavanja checkouta da korisnik prvo uhvati kontekst stranice.</p>
			</div>
		</div>

		<div class="zaher-popup-card__preview" data-popup-preview></div>
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
	$defaults        = zaher_get_checkout_popup_defaults();
	$saved_rows      = zaher_get_saved_checkout_popup_rows();
	$rows            = is_array( $saved_rows ) && ! empty( $saved_rows ) ? array_values( $saved_rows ) : array(
		array(
			'template_key'      => $default_template,
			'source_product_id' => 0,
			'target_product_id' => 0,
			'coupon_code'       => '',
			'timer_minutes'     => $defaults['timer_minutes'],
			'delay_seconds'     => $defaults['delay_seconds'],
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
		'defaults'       => array(
			'timerMinutes' => $defaults['timer_minutes'],
			'delaySeconds' => $defaults['delay_seconds'],
		),
		'currency'       => array(
			'symbol' => $currency_symbol,
			'after'  => $currency_after,
		),
	);
	?>
	<div class="wrap zaher-popup-settings">
		<style>
			.zaher-popup-settings .zaher-popup-settings__intro { max-width: 760px; color: #50575e; }
			.zaher-popup-settings .zaher-popup-settings__toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: 24px 0 18px; }
			.zaher-popup-settings .zaher-popup-settings__toolbar p { margin: 0; color: #646970; }
			.zaher-popup-settings .zaher-popup-settings__list { display: grid; gap: 18px; }
			.zaher-popup-settings .zaher-popup-card { background: #fff; border: 1px solid #dcdcde; border-radius: 14px; padding: 20px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04); }
			.zaher-popup-settings .zaher-popup-card.is-disabled { opacity: 0.72; }
			.zaher-popup-settings .zaher-popup-card__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
			.zaher-popup-settings .zaher-popup-card__actions { display: flex; align-items: center; gap: 14px; }
			.zaher-popup-settings .zaher-popup-card__title { margin: 0 0 4px; font-size: 18px; line-height: 1.3; }
			.zaher-popup-settings .zaher-popup-card__subtitle { margin: 0; color: #646970; }
			.zaher-popup-settings .zaher-popup-toggle { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
			.zaher-popup-settings .zaher-popup-toggle input[type="hidden"] { display: none; }
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"] { position: absolute; opacity: 0; pointer-events: none; }
			.zaher-popup-settings .zaher-popup-toggle__track { position: relative; width: 44px; height: 24px; border-radius: 999px; background: #c3c4c7; transition: background 0.2s ease; }
			.zaher-popup-settings .zaher-popup-toggle__track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.24); transition: transform 0.2s ease; }
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"]:checked + .zaher-popup-toggle__track { background: #2271b1; }
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"]:checked + .zaher-popup-toggle__track::after { transform: translateX(20px); }
			.zaher-popup-settings .zaher-popup-toggle input[type="checkbox"]:focus-visible + .zaher-popup-toggle__track { outline: 2px solid #2271b1; outline-offset: 2px; }
			.zaher-popup-settings .zaher-popup-toggle__label { font-weight: 600; color: #1d2327; }
			.zaher-popup-settings .zaher-popup-card__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 20px; }
			.zaher-popup-settings .zaher-popup-field { min-width: 0; }
			.zaher-popup-settings .zaher-popup-field label { display: block; font-weight: 600; margin-bottom: 6px; }
			.zaher-popup-settings .zaher-popup-field input,
			.zaher-popup-settings .zaher-popup-field select { width: 100%; max-width: none; }
			.zaher-popup-settings .zaher-popup-field .description { margin: 8px 0 0; color: #646970; }
			.zaher-popup-settings .zaher-popup-card__preview { margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: #f6f7f7; border: 1px solid #e2e4e7; color: #1d2327; display: grid; gap: 6px; }
			.zaher-popup-settings .zaher-popup-card__preview strong { font-weight: 600; }
			.zaher-popup-settings .zaher-popup-card__preview .is-warning { color: #b32d2e; }
			.zaher-popup-settings .zaher-popup-settings__footer { margin-top: 22px; display: flex; gap: 12px; align-items: center; }
			@media (max-width: 960px) {
				.zaher-popup-settings .zaher-popup-card__grid { grid-template-columns: 1fr; }
				.zaher-popup-settings .zaher-popup-settings__toolbar { align-items: flex-start; flex-direction: column; }
			}
		</style>

		<h1>Checkout Popup</h1>
		<p class="zaher-popup-settings__intro">Svaki popup povezuje jedan checkout s ciljanim planom, opcionalnim kuponom i unaprijed definiranim templateom. Stara cijena, nova cijena i CTA URL računaju se automatski iz MemberPress podataka.</p>

		<?php settings_errors( 'zaher_checkout_popups' ); ?>

		<form action="options.php" method="post" id="zaher-checkout-popup-form">
			<?php settings_fields( 'zaher_checkout_popup_settings' ); ?>
			<input type="hidden" name="zaher_checkout_popups[_present]" value="1" />

			<div class="zaher-popup-settings__toolbar">
				<p>Možeš imati više popupova, ali samo jedan po izvornom checkoutu.</p>
				<button type="button" class="button" id="zaher-add-popup">Dodaj popup</button>
			</div>

			<div class="zaher-popup-settings__list" id="zaher-popup-settings-list">
				<?php foreach ( $rows as $index => $popup ) : ?>
					<?php zaher_render_checkout_popup_row( $index, $popup, $source_products, $target_products, $coupon_choices, $template_choices ); ?>
				<?php endforeach; ?>
			</div>

			<div class="zaher-popup-settings__footer">
				<?php submit_button( 'Spremi postavke', 'primary', 'submit', false ); ?>
			</div>
		</form>

		<template id="zaher-popup-row-template">
			<?php zaher_render_checkout_popup_row( '__INDEX__', array(), $source_products, $target_products, $coupon_choices, $template_choices ); ?>
		</template>

		<script>
		(function() {
			const data = <?php echo wp_json_encode( $admin_data ); ?>;
			const list = document.getElementById('zaher-popup-settings-list');
			const addButton = document.getElementById('zaher-add-popup');
			const template = document.getElementById('zaher-popup-row-template');
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

			function formatMoney(amount) {
				const numericAmount = Number(amount || 0);
				const formatted = numericAmount.toLocaleString('hr-HR', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2,
				});

				return data.currency.after ? formatted + ' ' + data.currency.symbol : data.currency.symbol + formatted;
			}

			function applyCoupon(price, product, coupon) {
				let amount = Number(price || 0);

				if (!coupon || !product) {
					return amount;
				}

				if (!Array.isArray(coupon.validProductIds) || coupon.validProductIds.indexOf(Number(product.id)) === -1) {
					return amount;
				}

				if (coupon.discountMode === 'trial-override') {
					return amount;
				}

				let discountType = coupon.discountType;
				let discountAmount = Number(coupon.discountAmount || 0);

				if (coupon.discountMode === 'first-payment') {
					discountType = coupon.firstPaymentDiscountType;
					discountAmount = Number(coupon.firstPaymentDiscountAmount || 0);
				}

				if (discountType === 'percent') {
					amount = amount * (1 - (discountAmount / 100));
				} else {
					amount = amount - discountAmount;
				}

				return Math.max(0, amount);
			}

			function getPriceDisplay(product, coupon) {
				if (!product) {
					return 'Odaberi ciljanu pretplatu.';
				}

				const amount = formatMoney(applyCoupon(product.price, product, coupon));

				if (product.isOneTime) {
					return amount;
				}

				if (Number(product.period || 0) <= 1) {
					return amount + ' / ' + product.shortPeriodLabel;
				}

				return amount + ' / ' + product.period + ' ' + product.shortPeriodLabel;
			}

			function getOldPriceDisplay(source, target) {
				if (!target) {
					return 'Odaberi ciljanu pretplatu.';
				}

				return getPriceDisplay(target, null);
			}

			function getTargetUrl(target, coupon) {
				if (!target) {
					return '';
				}

				if (!coupon || !Array.isArray(coupon.validProductIds) || coupon.validProductIds.indexOf(Number(target.id)) === -1) {
					return target.url;
				}

				return target.url + (target.url.indexOf('?') === -1 ? '?' : '&') + 'coupon=' + encodeURIComponent(coupon.code);
			}

			function updateRow(row, rowIndex) {
				const sourceSelect = row.querySelector('[data-popup-source]');
				const targetSelect = row.querySelector('[data-popup-target]');
				const templateSelect = row.querySelector('[data-popup-template]');
				const couponSelect = row.querySelector('[data-popup-coupon]');
				const enabledField = row.querySelector('[data-popup-enabled]');
				const enabledLabel = row.querySelector('[data-popup-enabled-label]');
				const title = row.querySelector('[data-popup-title]');
				const subtitle = row.querySelector('[data-popup-subtitle]');
				const preview = row.querySelector('[data-popup-preview]');
				const isEnabled = !enabledField || enabledField.checked;
				const templateKey = templateSelect && templateSelect.value ? templateSelect.value : data.defaultTemplateKey;
				const templateMeta = templateKey ? data.templates[templateKey] || null : null;
				const source = sourceSelect ? getProduct(data.sourceProducts, sourceSelect.value) : null;
				const target = targetSelect ? getProduct(data.targetProducts, targetSelect.value) : null;
				const coupon = couponSelect && couponSelect.value ? data.coupons[couponSelect.value] || null : null;
				const couponValid = coupon && target && Array.isArray(coupon.validProductIds) && coupon.validProductIds.indexOf(Number(target.id)) !== -1;
				const ctaUrl = getTargetUrl(target, couponValid ? coupon : null);
				const templateMatchesTarget = !templateMeta || !templateMeta.recommendedPeriodType || (
					target &&
					target.periodType === templateMeta.recommendedPeriodType &&
					Number(target.period || 0) === Number(templateMeta.recommendedPeriod || 0)
				);

				if (title) {
					title.textContent = 'Popup #' + rowIndex;
				}

				row.classList.toggle('is-disabled', !isEnabled);

				if (enabledLabel) {
					enabledLabel.textContent = isEnabled ? 'Uključen' : 'Isključen';
				}

				if (subtitle) {
					if (source && target) {
						subtitle.textContent = source.title + ' -> ' + target.title + (templateMeta ? ' | ' + templateMeta.label : '') + (isEnabled ? '' : ' | Popup ugašen');
					} else if (source) {
						subtitle.textContent = source.title + ' -> odaberi ciljanu pretplatu' + (isEnabled ? '' : ' | Popup ugašen');
					} else {
						subtitle.textContent = 'Odaberi checkout na kojem se popup prikazuje i pretplatu na koju vodi CTA.' + (isEnabled ? '' : ' Popup je trenutno ugašen.');
					}
				}

				if (preview) {
					const lines = [
						'<div><strong>Status:</strong> ' + (isEnabled ? 'Uključen' : 'Isključen') + '</div>',
						'<div><strong>Template:</strong> ' + (templateMeta ? templateMeta.label : 'Odaberi template.') + '</div>',
						'<div><strong>Redovna cijena:</strong> ' + getOldPriceDisplay(source, target) + '</div>',
						'<div><strong>Danas plaćaš:</strong> ' + getPriceDisplay(target, couponValid ? coupon : null) + '</div>',
						'<div><strong>CTA URL:</strong> ' + (ctaUrl ? ctaUrl : 'Odaberi ciljanu pretplatu.') + '</div>',
					];

					if (couponValid && coupon && (coupon.discountMode === 'first-payment' || coupon.discountMode === 'trial-override') && target) {
						lines.splice(3, 0, '<div><strong>Obnova nakon popusta:</strong> ' + getPriceDisplay(target, null) + '</div>');
					}

					if (coupon && !couponValid) {
						lines.push('<div class="is-warning"><strong>Napomena:</strong> odabrani kupon nije valjan za ciljanu pretplatu.</div>');
					}

					if (templateMeta && templateMeta.recommendedPeriodType && target && !templateMatchesTarget) {
						lines.push('<div class="is-warning"><strong>Napomena:</strong> odabrani template copy je pisan za ciljanu pretplatu od 3 mjeseca.</div>');
					}

					preview.innerHTML = lines.join('');
				}
			}

			function updateAllRows() {
				Array.from(list.querySelectorAll('[data-popup-row]')).forEach(function(row, index) {
					updateRow(row, index + 1);
				});
			}

			function bindRow(row) {
				row.querySelectorAll('select, input').forEach(function(field) {
					field.addEventListener('change', updateAllRows);
					field.addEventListener('input', updateAllRows);
				});

				const removeButton = row.querySelector('[data-remove-popup]');
				if (removeButton) {
					removeButton.addEventListener('click', function() {
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
		})();
		</script>
	</div>
	<?php
}
