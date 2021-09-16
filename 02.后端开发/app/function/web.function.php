<?php

/*
 * web相关函数
 */

function get_client_ip() {
    $arr_ip_header = array(
        "HTTP_CLIENT_IP",
        "HTTP_X_FORWARDED_FOR",
        "REMOTE_ADDR",
        "HTTP_CDN_SRC_IP",
        "HTTP_PROXY_CLIENT_IP",
        "HTTP_WL_PROXY_CLIENT_IP"
    );
    $client_ip = 'unknown';
    foreach ($arr_ip_header as $key) {
        if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != "unknown") {
            $client_ip = $_SERVER[$key];
            break;
        }
    }
    if ($pos = strpos($client_ip, ',')) {
        $client_ip = substr($client_ip, $pos + 1);
    }
    return $client_ip;
}

function get_host() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? 'https://' : 'http://';

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            strlen($_SERVER['HTTP_X_FORWARDED_PROTO']) > 0) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://';
    }
    $url_host = $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT']);
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $url_host;
    $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $host; //proxy
    return $protocol . $host;
}
