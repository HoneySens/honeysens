<?php
namespace HoneySens\app\models;

class Utils {

    /**
     * Shortens the given base64 string into the limit given by $characters.
     *
     * @param int $characters
     * @param string $input
     * @return string
     */
    static function shortenBase64($characters, $input) {
        if(strlen($input) > $characters) {
            $maxSourceLength = floor($characters / 4) * 3;
            return base64_encode(substr(base64_decode($input), 0, $maxSourceLength));
        } else return $input;
    }
}
