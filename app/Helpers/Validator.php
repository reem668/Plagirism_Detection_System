<?php
namespace Helpers;

class Validator {
    public static function sanitize($s){
        return trim(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));
    }
}
