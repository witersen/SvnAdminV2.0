<?php
/*
 * @Author: witersen
 * @Date: 2022-05-03 02:20:26
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 17:57:17
 * @Description: QQ:1801168257
 */

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    'files' => [
        base_path() . '/app/functions.php',
        base_path() . '/support/Request.php',
        base_path() . '/support/Response.php',

        base_path() . '/app/function/array.function.php',
        base_path() . '/app/function/color.function.php',
        base_path() . '/app/function/curl.function.php',
        base_path() . '/app/function/file.function.php',
        base_path() . '/app/function/json.function.php',
        base_path() . '/app/function/return.function.php',
        base_path() . '/app/function/string.function.php',
        base_path() . '/app/function/update.function.php',

        base_path() . '/app/service/SVNAdmin/src/core/Core.class.php',
        base_path() . '/app/service/SVNAdmin/src/class/Group.class.php',
        base_path() . '/app/service/SVNAdmin/src/class/Rep.class.php',
        base_path() . '/app/service/SVNAdmin/src/class/User.class.php',

        base_path() . '/app/service/check.service.php',
        base_path() . '/app/service/download.service.php',
        base_path() . '/app/service/shell.service.php',
        base_path() . '/app/service/verifycode.service.php',
    ]
];
