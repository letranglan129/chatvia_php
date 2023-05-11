<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token {

    private static $secret = '7la_HDBlahsyb01h';
    private static $type =  'HS256';

    public static function encode($payload)
    {
        try {
            return JWT::encode($payload, Token::$secret, Token::$type);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage);
        }
    }

    public static function decode($payload) {
        try {
            return JWT::decode($payload, new Key(Token::$secret, Token::$type));
        } catch (\Throwable $th) {
            return json_encode($th);
        }
    }

}