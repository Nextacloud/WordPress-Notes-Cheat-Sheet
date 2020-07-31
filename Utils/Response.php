<?php

class Response
{
    public static function send_error_response($message)
    {
        $status_code = 200;
        $response = ['success' => false, 'message' => $message];
        wp_send_json($response, $status_code);
    }

}