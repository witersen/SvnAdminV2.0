<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-11 19:58:31
 * @Description: QQ:1801168257
 */

/**
 * 升级服务器地址
 */
return [
    'update_server' => [
        [
            //主更新节点 提供常规的 检测更新和及时的beat版本、紧急修复版本等
            'nodeName' => 'witersen.com',
            'url' => 'http://update.witersen.com/svnadmin/api.php?c=Update&a=Detect&version=%s'
        ]
    ]
];
