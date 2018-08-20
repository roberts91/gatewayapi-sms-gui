<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class ActionHandling
 */
class ActionHandling {

	/** @var string Cookie prefix for cookie storage. */
	private $cookiePrefix = 'apekatt_';

	/** @var bool Whether cookies have been cleared during this request. */
	private $cookiesCleared = false;

	/** @var array Array containing field validation errors. */
	public $errors = [];

	/** @var null Boolean telling us if the message got sent. */
	public $result = null;

	/** @var null Form values. */
	public $values = null;

	/**
	 * ActionHandling constructor.
	 */
	public function __construct() {
		$this->handleSubmission();
	}

	/**
	 * Handle validation error output from API.
	 * @param $response
	 *
	 * @return string
	 */
	private function handleApiValidationError( $response ) {
		$messages = [];
		if ( isset( $response->message ) ) {
			$messages[] = $response->message;
		} else {
			foreach ( $response->fields as $key => $value ) {
				foreach ( $value as $message ) {
					$messages[] = sprintf( 'Field "%s" – %s', $key, $message->invalid );
				}
			}
		}
		return implode( '<br>', $messages );
	}

	/**
	 * Handle form validation error output.
	 *
	 * @param $errorMessageMask
	 */
	public function handleValidationErrors( $errorMessageMask ) {
		foreach ( $this->errors as $key => $error_groups ) {
			foreach ( $error_groups as $error_group ) {
				if ( $key ==  'field_errors' ) {
					foreach ( $error_group as $error ) {
						printf( $errorMessageMask, $error );
					}
				} elseif ( $key == 'response' ) {
					if ( ! $error_group['result'] ) {
						printf( $errorMessageMask, 'Could not send SMS. Response from API:<br>' . $this->handleApiValidationError( $error_group['response'] ) );
					}
				}
			}
		}
	}

	/**
	 * Parse receivers.
	 *
	 * @param $receivers
	 *
	 * @return array
	 */
	private function parseReceivers( $receivers ) {
		$receivers = explode( ',', $receivers );
		$receivers = array_filter( $receivers, function( $receiver ) {
			return ! empty( $receiver );
		} );
		$receivers = array_map( function ( $receiver ) {
			return [ 'msisdn' => trim( $receiver ) ];
		}, $receivers );
		return $receivers;
	}

	/**
	 * Get parameters from request.
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	private function getPostParam( $key ) {
		if ( ! array_key_exists( $key, $_POST ) ) {
			return false;
		}
		return $_POST[ $key ];
	}

	private $isPost = null;

	/**
	 * Check if POST-request.
	 *
	 * @return bool
	 */
	public function isPost() {
		if ( is_null( $this->isPost ) ) {
			$this->isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
		}
		return $this->isPost;
	}

	/**
	 * Validate parameters.
	 *
	 * @param $from
	 * @param $receivers
	 * @param $message
	 *
	 * @return bool
	 */
	private function validateParameters( $from, $receivers, $message ) {

		$formValid = true;

		// Validate receivers
		if ( empty( $receivers ) ) {
			$this->addError( 'Receivers-field missing value', 'field_errors', 'receivers' );
			$formValid = false;
		}
		if ( ! empty( $receivers ) && ! ctype_digit( str_replace( ',', '', $receivers ) ) ) {
			$this->addError( 'Receivers-field contains non-digit characters (only command and digits allowed)', 'field_errors', 'receivers' );
			$formValid = false;
		}

		// Validate from-field
		if ( empty( $from ) ) {
			$this->addError( 'From-field missing value', 'field_errors', 'from' );
			$formValid = false;
		}

		// Validate message
		if ( empty( $message ) ) {
			$this->addError( 'Message-field missing value', 'field_errors', 'message' );
			$formValid = false;
		}

		return $formValid;

	}

	/**
	 * Add error message.
	 *
	 * @param $message
	 * @param $domain
	 * @param bool $field
	 */
	private function addError( $message, $domain, $field = false ) {
		if ( $field ) {
			$this->errors[$domain][$field][] = $message;
		} else {
			$this->errors[$domain][] = $message;
		}
	}

	/**
	 * Get cookie value.
	 *
	 * @param $key
	 * @param bool $textField
	 *
	 * @return string|void
	 */
	public function getCookieValue( $key, $textField = false ) {

		$postValue = $this->getPostParam( $key );
		$cookieValue = ( isset( $_COOKIE[ $this->cookiePrefix . $key ] ) ? $_COOKIE[ $this->cookiePrefix . $key ] : false );

		// Cookies cleared, show empty field
		if ( $textField && $this->cookiesCleared ) {
			return '';
		}

		// The cookie has changed, show the updated one
		if ( ! empty( $postValue ) && $postValue != $cookieValue ) {
			return $postValue;
		}

		// We got a cookie value, show that one
		if ( $cookieValue ) {
			return $cookieValue;
		}

		// Return post value
		return $postValue;

	}

	/**
	 * Clear cookie values.
	 */
	private function clearCookieValues() {
		$expiryTime = time() - 3600;
		$this->setCookieValue( 'api_key', '', $expiryTime );
		$this->setCookieValue( 'api_secret', '', $expiryTime );
		$this->cookiesCleared = true;
	}

	/**
	 * Set cookie value.
	 *
	 * @param $key
	 * @param $value
	 * @param bool $expire
	 */
	private function setCookieValue( $key, $value, $expire = false ) {
		if ( ! $expire ) {
			$expire = time() + ( 86400 * 30 );
		}
		setcookie( $this->cookiePrefix . $key, $value, $expire, '/' );
	}

	/**
	 * Get form values.
	 *
	 * @return object
	 */
	private function getValues() {
		return (object) [
			'from'      => $this->getPostParam( 'from' ),
			'receivers' => $this->getPostParam( 'receivers' ),
			'message'   => $this->getPostParam( 'message' ),
		];
	}

	/**
	 * Check if custom API credentials was submitted.
	 */
	private function handleApiCredentials() {
		$clear = ( $this->getPostParam( 'api_clear_cookie' ) === '1' );
		if ( $clear ) {
			$this->clearCookieValues();
			return;
		}
		$key = $this->getPostParam( 'api_key' );
		$secret = $this->getPostParam( 'api_secret' );
		if ( ! empty( $key ) ) {
			$this->setCookieValue( 'api_key', $key );
		}
		if ( ! empty( $secret ) ) {
			$this->setCookieValue( 'api_secret', $secret );
		}
	}

	/**
	 * Check if we got any values from the form.
	 */
	private function hasValues() {
		foreach ( $this->values as $value ) {
			if ( ! empty( $value ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Handle submission.
	 */
	private function handleSubmission() {
		if ( $this->isPost() ) {

			// Handle API credentials submission
			$this->handleApiCredentials();

			// Get form values
			$this->values = $this->getValues();

			// Bail if this request was only to clear cookie and we have no data.
			if ( $this->cookiesCleared && ! $this->hasValues( )) {
				$this->isPost = false;
				return;
			}

			// Do validation
			if ( ! $this->validateParameters( $this->values->from, $this->values->receivers, $this->values->message ) ) {
				$this->result = false;
				return;
			}

			// Try to send
			$result = $this->sendSMS( $this->values->from, $this->parseReceivers( $this->values->receivers ), $this->values->message );
			if ( $result['result'] ) {
				$this->result = true;
			} else {
				$this->addError( $result, 'response' );
				$this->result = false;
			}

		}
	}

	/**
	 * @param $key
	 * @param bool $textField
	 *
	 * @return null|string
	 */
	public function getFormValue( $key, $textField = false ) {
		if ( ! is_object( $this->values ) || ! property_exists( $this->values, $key ) ) {
			return null;
		}
		$value = $this->values->{ $key };
		if ( $textField ) {
			$value = htmlspecialchars( $value );
		}
		return $value;
	}

	private function getApiCredentials() {
		$storedKey = $this->getCookieValue( 'api_key' );
		$storedSecret = $this->getCookieValue( 'api_secret' );
		if ( ! empty( $storedKey ) || ! empty( $storedSecret ) ) {
			return [
				'key' => $storedKey,
				'secret' => $storedSecret,
			];
		} else {
			return [
				'key' => getenv( 'consumer_key' ),
				'secret' => getenv('consumer_secret' ),
			];
		}
	}

	/**
	 * Send SMS.
	 *
	 * @param $from
	 * @param $receivers
	 * @param $message
	 *
	 * @return array
	 */
	private function sendSMS( $from, $receivers, $message ) {
		$apiCredentials = $this->getApiCredentials();
		$stack = \GuzzleHttp\HandlerStack::create();
		$oauth_middleware = new \GuzzleHttp\Subscriber\Oauth\Oauth1( [
			'consumer_key'    => $apiCredentials['key'],
			'consumer_secret' => $apiCredentials['secret'],
			'token'           => '',
			'token_secret'    => ''
		] );
		$stack->push( $oauth_middleware );
		$client = new \GuzzleHttp\Client( [
			'base_uri' => 'https://gatewayapi.com/rest/',
			'handler'  => $stack,
			'auth'     => 'oauth'
		] );
		$req = [
			'sender'     => $from,
			'recipients' => $receivers,
			'message'    => $message,
		];
		$res = $client->post( 'mtsms', [ 'json' => $req, 'http_errors' => false ] );
		return [
			'result' => ( $res->getStatusCode() === 200 ),
			'response' => json_decode( $res->getBody() ),
		];
	}

}
$s = new ActionHandling;