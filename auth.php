<?php

/**
 * Basic authentication.
 *
 * @param $user_array
 */
function require_auth( $user_array ) {
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	$has_supplied_credentials = ! ( empty( $_SERVER['PHP_AUTH_USER'] ) && empty( $_SERVER['PHP_AUTH_PW'] ) );
	$is_authenticated = false;
	if ( $has_supplied_credentials ) {
		foreach ( $user_array as $user ) {
			if ( $_SERVER['PHP_AUTH_USER'] == $user['user'] && $_SERVER['PHP_AUTH_PW']   == $user['pass'] ) {
				$is_authenticated = true;
				break;
			}
		}
	}
	if ( ! $is_authenticated ) {
		header( 'HTTP/1.1 401 Authorization Required' );
		header( 'WWW-Authenticate: Basic realm="Authentication required - Sagem Modem Status Page"' );
		exit;
	}
}