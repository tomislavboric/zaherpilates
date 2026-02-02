<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPMLS_MailerLite_Client {
	const API_BASE = 'https://connect.mailerlite.com/api';

	protected $api_key;

	public function __construct( $api_key ) {
		$this->api_key = trim( (string) $api_key );
	}

	public function test_connection() {
		return $this->request( 'GET', '/groups?limit=1' );
	}

	public function upsert_subscriber( $email, $fields = array() ) {
		$payload = array(
			'email' => $email,
		);
		if ( ! empty( $fields ) ) {
			$payload['fields'] = $fields;
		}

		$response = $this->request( 'POST', '/subscribers', $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data']['data']['id'] ) ) {
			return new WP_Error( 'mpmls_no_subscriber_id', 'MailerLite did not return a subscriber ID.' );
		}

		return $response['data']['data']['id'];
	}

	public function add_to_group( $subscriber_id, $group_id ) {
		$response = $this->request( 'POST', '/subscribers/' . rawurlencode( (string) $subscriber_id ) . '/groups/' . rawurlencode( (string) $group_id ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	public function remove_from_group( $subscriber_id, $group_id ) {
		$response = $this->request( 'DELETE', '/subscribers/' . rawurlencode( (string) $subscriber_id ) . '/groups/' . rawurlencode( (string) $group_id ) );
		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();
			if ( 'mpmls_http_404' === $code ) {
				return true;
			}
			return $response;
		}

		return true;
	}

	public function get_subscriber_id_by_email( $email ) {
		$response = $this->request( 'GET', '/subscribers/' . rawurlencode( (string) $email ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data']['data']['id'] ) ) {
			return new WP_Error( 'mpmls_no_subscriber_id', 'MailerLite did not return a subscriber ID.' );
		}

		return $response['data']['data']['id'];
	}

	protected function request( $method, $endpoint, $body = null, $retry = 1 ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'mpmls_missing_api_key', 'MailerLite API key is missing.' );
		}

		$args = array(
			'timeout' => 10,
			'method'  => strtoupper( $method ),
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::API_BASE . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			if ( $retry > 0 ) {
				return $this->request( $method, $endpoint, $body, $retry - 1 );
			}
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw    = (string) wp_remote_retrieve_body( $response );

		if ( $status >= 200 && $status < 300 ) {
			$data = array();
			if ( $raw !== '' ) {
				$decoded = json_decode( $raw, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$data = $decoded;
				} else {
					return new WP_Error( 'mpmls_json_error', 'MailerLite returned invalid JSON.' );
				}
			}
			return array(
				'status' => $status,
				'data'   => $data,
			);
		}

		if ( ( $status >= 500 || 429 === $status ) && $retry > 0 ) {
			return $this->request( $method, $endpoint, $body, $retry - 1 );
		}

		$message = '';
		if ( $raw !== '' ) {
			$decoded = json_decode( $raw, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( isset( $decoded['message'] ) && $decoded['message'] !== '' ) {
					$message = $decoded['message'];
				} elseif ( isset( $decoded['error'] ) && $decoded['error'] !== '' ) {
					$message = $decoded['error'];
				} elseif ( isset( $decoded['errors'] ) && is_array( $decoded['errors'] ) ) {
					$first = reset( $decoded['errors'] );
					if ( is_array( $first ) ) {
						if ( ! empty( $first['message'] ) ) {
							$message = $first['message'];
						} elseif ( ! empty( $first['error'] ) ) {
							$message = $first['error'];
						} elseif ( ! empty( $first['detail'] ) ) {
							$message = $first['detail'];
						}
					} elseif ( is_string( $first ) && $first !== '' ) {
						$message = $first;
					}
				}
			} else {
				$message = wp_strip_all_tags( substr( $raw, 0, 200 ) );
			}
		}

		if ( $message === '' ) {
			$message = wp_remote_retrieve_response_message( $response );
		}

		if ( $message === '' ) {
			$message = 'MailerLite request failed.';
		}

		return new WP_Error( 'mpmls_http_' . $status, 'MailerLite request failed (HTTP ' . $status . '): ' . $message );
	}
}
