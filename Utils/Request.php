<?php
class Request
{

    const SECRET = '';

    // ensure it is a post request
    public static function validate_post_request()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::send_error_response('Must be a POST request');
        }
    }

    public static function validate_get_request()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::send_error_response('Must be a GET request');
        }
    }

    public static function validate_nonce()
    {
        // if both posted or getted nonce validation is false
        if ( ! wp_verify_nonce( $_POST['nonce'], self::SECRET) && ! wp_verify_nonce( $_GET['nonce'], self::SECRET) ) {
            Response::send_error_response('Nonce not valid!');
        }

        // resume if one of them is true
    }

    public static function get_nonce()
    {
        return wp_create_nonce( self::SECRET );
    }

}