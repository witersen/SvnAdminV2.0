<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * Provides whether the string is already encoded in UTF-8, if not
 * it will encode it to UTF-8.
 *
 * @param string $data
 * @return string Encoded with UTF-8.
 */
function if_ensure_utf8_encoding($data)
{
    if (function_exists("mb_detect_encoding")) {
        if (mb_detect_encoding($data) == "UTF-8") {
            return $data;
        } else {
            return utf8_encode($data);
        }
    }
    return $data;
}

/**
 * Makes sure that the string is not encoded in UTF-8 format. If it is encoded
 * with UTF-8, it will automaticaliy beeing decoded.
 *
 * @param string $data
 * @return string The decoded string
 */
function if_ensure_utf8_decoding($data)
{
    if (function_exists("mb_detect_encoding")) {
        if (mb_detect_encoding($data) == "UTF-8") {
            return utf8_decode($data);
        } else {
            return $data;
        }
    }
    return $data;
}
