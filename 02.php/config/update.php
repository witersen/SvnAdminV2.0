<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
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
            'url' => 'https://update.witersen.com/svnadmin/api.php?c=Update&a=Detect&version=%s'
        ]
    ]
];
