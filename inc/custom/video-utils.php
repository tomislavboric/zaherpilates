<?php
/**
 * Parse the "Video length" ACF field into minutes when possible.
 * Supports formats like: "15", "15 min", "15min", "15:00", "00:15:00".
 */
function zaher_parse_video_length_minutes( $value ) {
	$value = trim( (string) $value );
	if ( $value === '' ) {
		return null;
	}

	// HH:MM:SS or MM:SS
	if ( preg_match( '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m ) ) {
		$h   = 0;
		$min = 0;
		$sec = 0;
		if ( isset( $m[3] ) && $m[3] !== '' ) {
			// HH:MM:SS
			$h   = (int) $m[1];
			$min = (int) $m[2];
			$sec = (int) $m[3];
		} else {
			// MM:SS
			$min = (int) $m[1];
			$sec = (int) $m[2];
		}
		$total = ( $h * 60 ) + $min + ( $sec >= 30 ? 1 : 0 );
		return $total > 0 ? $total : null;
	}

	// "15", "15 min", "15min"
	if ( preg_match( '/\b(\d{1,3})\b/', $value, $m ) ) {
		$min = (int) $m[1];
		return $min > 0 ? $min : null;
	}

	return null;
}

// Extracts ID from Vimeo link.
function getVimeoVideoId( $vimeoUrl ) {
	$parts = parse_url( $vimeoUrl );
	if ( isset( $parts['path'] ) ) {
		$pathParts = explode( '/', trim( $parts['path'], '/' ) );
		return $pathParts[ count( $pathParts ) - 1 ];
	}
	return false;
}
