<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPMLS_MailerLite_Client {
	const API_BASE_NEW = 'https://connect.mailerlite.com/api';
	const API_BASE_CLASSIC = 'https://api.mailerlite.com/api/v2';
	const API_VERSION = '2022-11-21';

	protected $api_key;
	protected $api_type;

	public function __construct( $api_key ) {
		$this->api_key = trim( (string) $api_key );
		$this->api_type = $this->detect_api_type( $this->api_key );
	}

	public function test_connection() {
		if ( $this->is_classic() ) {
			return $this->request( 'GET', '/groups?limit=1' );
		}

		return $this->request( 'GET', '/account' );
	}

	public function list_groups( $limit = 100 ) {
		if ( $this->is_classic() ) {
			$response = $this->request( 'GET', '/groups', array( 'limit' => $limit ) );
		} else {
			$response = $this->request( 'GET', '/groups', array( 'limit' => $limit ) );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response['data'];
		$groups = array();

		if ( $this->is_classic() ) {
			$items = is_array( $data ) ? $data : array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) ) {
					$groups[] = array(
						'id'   => (string) $item['id'],
						'name' => isset( $item['name'] ) ? (string) $item['name'] : (string) $item['id'],
					);
				}
			}
		} else {
			$items = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) ) {
					$groups[] = array(
						'id'   => (string) $item['id'],
						'name' => isset( $item['name'] ) ? (string) $item['name'] : (string) $item['id'],
					);
				}
			}
		}

		return $groups;
	}

	public function upsert_subscriber( $email, $fields = array() ) {
		$payload = array( 'email' => $email );
		if ( ! empty( $fields ) ) {
			$payload['fields'] = $fields;
		}
		if ( $this->is_classic() ) {
			$payload['resubscribe'] = 1;
		}

		$response = $this->request( 'POST', '/subscribers', $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$subscriber_id = $this->extract_subscriber_id( $response );
		if ( ! $subscriber_id ) {
			return new WP_Error( 'mpmls_no_subscriber_id', 'MailerLite did not return a subscriber ID.' );
		}

		return $subscriber_id;
	}

	public function add_to_group( $subscriber_id, $group_id, $email = '', $fields = array() ) {
		if ( $this->is_classic() ) {
			$payload = array(
				'email' => $email,
				'fields' => $fields,
				'resubscribe' => 1,
			);
			$response = $this->request( 'POST', '/groups/' . rawurlencode( (string) $group_id ) . '/subscribers', $payload );
		} else {
			$response = $this->request( 'POST', '/subscribers/' . rawurlencode( (string) $subscriber_id ) . '/groups/' . rawurlencode( (string) $group_id ) );
		}
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	public function remove_from_group( $subscriber_id, $group_id, $email = '' ) {
		if ( $this->is_classic() ) {
			$target = $subscriber_id ? $subscriber_id : $email;
			$response = $this->request( 'DELETE', '/groups/' . rawurlencode( (string) $group_id ) . '/subscribers/' . rawurlencode( (string) $target ) );
		} else {
			$response = $this->request( 'DELETE', '/subscribers/' . rawurlencode( (string) $subscriber_id ) . '/groups/' . rawurlencode( (string) $group_id ) );
		}
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

		$subscriber_id = $this->extract_subscriber_id( $response );
		if ( ! $subscriber_id ) {
			return new WP_Error( 'mpmls_no_subscriber_id', 'MailerLite did not return a subscriber ID.' );
		}

		return $subscriber_id;
	}

	protected function request( $method, $endpoint, $body = null, $retry = 1 ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'mpmls_missing_api_key', 'MailerLite API key is missing.' );
		}

		$url = $this->get_api_base() . $endpoint;
		$headers = $this->get_headers();

		$args = array(
			'timeout' => 10,
			'method'  => strtoupper( $method ),
			'headers' => $headers,
		);

		if ( null !== $body && strtoupper( $method ) === 'GET' && is_array( $body ) ) {
			$url = add_query_arg( $body, $url );
		} elseif ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

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

	protected function detect_api_type( $key ) {
		$type = ( strlen( $key ) < 100 ) ? 'classic' : 'new';
		return apply_filters( 'mpmls_mailerlite_api_type', $type, $key );
	}

	protected function is_classic() {
		return $this->api_type === 'classic';
	}

	protected function get_api_base() {
		return $this->is_classic() ? self::API_BASE_CLASSIC : self::API_BASE_NEW;
	}

	protected function get_headers() {
		if ( $this->is_classic() ) {
			return array(
				'X-MailerLite-ApiKey' => $this->api_key,
				'Accept'              => 'application/json',
				'Content-Type'        => 'application/json',
			);
		}

		return array(
			'Authorization' => 'Bearer ' . $this->api_key,
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'X-Version'     => self::API_VERSION,
		);
	}

	protected function extract_subscriber_id( $response ) {
		if ( $this->is_classic() ) {
			if ( isset( $response['data']['id'] ) ) {
				return $response['data']['id'];
			}
			if ( isset( $response['data']['data']['id'] ) ) {
				return $response['data']['data']['id'];
			}
			if ( isset( $response['data']['subscriber']['id'] ) ) {
				return $response['data']['subscriber']['id'];
			}
			return null;
		}

		return isset( $response['data']['data']['id'] ) ? $response['data']['data']['id'] : null;
	}
}
