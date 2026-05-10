<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( function_exists( 'theme_is_memberpress_auth_context' ) && theme_is_memberpress_auth_context() ) {
	return;
}
?>

<?php if ( isset( $errors ) && null !== $errors && count( $errors ) > 0 ) : ?>
	<div class="mepr-auth__message mepr-auth__message--error" id="mepr_jump">
		<ul>
			<?php foreach ( $errors as $error ) : ?>
				<li><?php echo MeprAppHelper::wp_kses( $error ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<?php if ( isset( $message ) && ! empty( $message ) ) : ?>
	<div class="mepr-auth__message mepr-auth__message--success">
		<?php echo MeprAppHelper::wp_kses( $message ); ?>
	</div>
<?php endif; ?>
