<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPMLS_MailerLite_Client {
	const API_BASE_NEW = 'https://connect.mailerlite.com/api';
	const API_BASE_CLASSIC = 'https://api.mailerlite.com/api/v2';
	const API_VERSION = '2022-11-21';
	const MIN_REQUEST_INTERVAL = 1.0;

	protected $api_key;
	protected $api_type;
	protected static $last_request_at = 0.0;

	public function __construct( $api_key ) {
		$this->api_key = trim( (string) $api_key );
		$this->api_type = $this->detect_api_type( $this->api_key );
	}

	public function test_connection() {
		if ( $this->is_classic() ) {
			return $this->request( 'GET', '/groups?limit=1', null, 1, true );
		}

		return $this->request( 'GET', '/account', null, 1, true );
	}

	public function list_groups( $limit = 100 ) {
		$response = $this->request( 'GET', '/groups', array( 'limit' => $limit ), 1, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response['data'];
		$groups = array();

		// active_count is what MailerLite shows as the group's subscriber
		// number: mailable contacts only, so unsubscribed and bounced members
		// are excluded. Null when the API does not report it.
		if ( $this->is_classic() ) {
			$items = is_array( $data ) ? $data : array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) ) {
					$groups[] = array(
						'id'           => (string) $item['id'],
						'name'         => isset( $item['name'] ) ? (string) $item['name'] : (string) $item['id'],
						'active_count' => isset( $item['active'] ) ? (int) $item['active'] : null,
					);
				}
			}
		} else {
			$items = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) ) {
					$groups[] = array(
						'id'           => (string) $item['id'],
						'name'         => isset( $item['name'] ) ? (string) $item['name'] : (string) $item['id'],
						'active_count' => isset( $item['active_count'] ) ? (int) $item['active_count'] : null,
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
		// Never reactivate a contact who unsubscribed or bounced. MailerLite's
		// current API preserves the existing status when it is omitted, while
		// the Classic API's resubscribe flag would override that choice.

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
				'email'       => $email,
				'fields'      => $fields,
				'resubscribe' => 0,
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

	/**
	* One page of a group's ACTIVE members (new API only). Only active-status
	* subscribers inflate the member count MailerLite shows for a group;
	* unsubscribed and bounced contacts are left untouched so their history
	* survives.
	*
	* $page_args is the opaque 'next' value returned by the previous call
	* (empty array for the first page). Returns
	* array( 'subscribers' => array( array( id, email, status ) ), 'next' => array|null ).
	*/
	public function get_group_subscribers( $group_id, $page_args = array() ) {
		if ( $this->is_classic() ) {
			return new WP_Error( 'mpmls_prune_unsupported', 'Group member listing requires the new MailerLite API.' );
		}

		$query = array(
			'limit'          => 100,
			'filter[status]' => 'active',
		);
		if ( ! empty( $page_args['cursor'] ) ) {
			$query['cursor'] = (string) $page_args['cursor'];
		} elseif ( ! empty( $page_args['page'] ) ) {
			$query['page'] = (int) $page_args['page'];
		}

		$response = $this->request( 'GET', '/groups/' . rawurlencode( (string) $group_id ) . '/subscribers', $query );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data  = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();
		$items = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();

		$subscribers = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['id'] ) ) {
				continue;
			}
			$subscribers[] = array(
				'id'     => (string) $item['id'],
				'email'  => isset( $item['email'] ) ? (string) $item['email'] : '',
				'status' => isset( $item['status'] ) ? (string) $item['status'] : '',
			);
		}

		// MailerLite paginates this endpoint with a cursor or with page numbers
		// depending on API revision — support both.
		$next = null;
		$meta = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();
		if ( ! empty( $meta['next_cursor'] ) ) {
			$next = array( 'cursor' => (string) $meta['next_cursor'] );
		} elseif ( isset( $meta['current_page'], $meta['last_page'] ) && (int) $meta['current_page'] < (int) $meta['last_page'] ) {
			$next = array( 'page' => (int) $meta['current_page'] + 1 );
		}
		if ( empty( $subscribers ) ) {
			$next = null;
		}

		return array(
			'subscribers' => $subscribers,
			'next'        => $next,
		);
	}

	protected function request( $method, $endpoint, $body = null, $retry = 1, $allow_fallback = false ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'mpmls_missing_api_key', 'MailerLite API key is missing.' );
		}

		$this->throttle_requests();

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
			if ( 429 === $status ) {
				$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
				if ( $retry_after !== '' ) {
					$wait = max( 1, (int) $retry_after );
					sleep( $wait );
				}
			}
			return $this->request( $method, $endpoint, $body, $retry - 1, $allow_fallback );
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

		$error = new WP_Error( 'mpmls_http_' . $status, 'MailerLite request failed (HTTP ' . $status . '): ' . $message );

		if ( $allow_fallback && in_array( $status, array( 401, 403 ), true ) ) {
			$this->toggle_api_type();
			return $this->request( $method, $endpoint, $body, $retry, false );
		}

		return $error;
	}

	protected function throttle_requests() {
		$min_interval = apply_filters( 'mpmls_mailerlite_min_interval', self::MIN_REQUEST_INTERVAL );
		$min_interval = is_numeric( $min_interval ) ? (float) $min_interval : self::MIN_REQUEST_INTERVAL;
		if ( $min_interval <= 0 ) {
			return;
		}

		$now = microtime( true );
		if ( self::$last_request_at > 0 ) {
			$elapsed = $now - self::$last_request_at;
			if ( $elapsed < $min_interval ) {
				usleep( (int) ( ( $min_interval - $elapsed ) * 1000000 ) );
			}
		}
		self::$last_request_at = microtime( true );
	}

	protected function detect_api_type( $key ) {
		$type = ( strlen( $key ) < 100 ) ? 'classic' : 'new';
		return apply_filters( 'mpmls_mailerlite_api_type', $type, $key );
	}

	protected function is_classic() {
		return $this->api_type === 'classic';
	}

	protected function toggle_api_type() {
		$this->api_type = $this->is_classic() ? 'new' : 'classic';
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
