<?php
/*
 * @Author: witersen
 * @Date: 2022-05-06 18:41:32
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 13:15:21
 * @Description: QQ:1801168257
 */

namespace app\controller;

//require config
auto_require(BASE_PATH . '/config/');

//require function
auto_require(BASE_PATH . '/app/function/');

//require util
auto_require(BASE_PATH . '/app/util/', true);

//require controller
auto_require(BASE_PATH . '/app/controller/');

//require service
auto_require(BASE_PATH . '/app/service/base/Base.php');
auto_require(BASE_PATH . '/app/service/');

//require extension
auto_require(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php');

auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/Exception.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/PHPMailer.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/SMTP.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/language/phpmailer.lang-zh_cn.php');

auto_require(BASE_PATH . '/extension/Verifycode/Verifycode.php');

auto_require(BASE_PATH . '/extension/Witersen/SVNAdmin.php');

function auto_require($path, $recursively = false)
{
    if (is_file($path)) {
        if (substr($path, -4) == '.php') {
            require_once $path;
        }
    } else {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $recursively ? auto_require($path . '/' . $file, true) : '';
                } else {
                    if (substr($file, -4) == '.php') {
                        require_once $path . '/' . $file;
                    }
                }
            }
        }
    }
}

class Base
{
    function __construct()
    {
    }
}
