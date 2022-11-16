<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

function funGetColor($percent)
{
    $array = [
        [
            'title' => '运行堵塞',
            'value' => 100,
            'color' => '#ed4014'
        ],
        [
            'title' => '运行缓慢',
            'value' => 90,
            'color' => '#ff9900'
        ],
        [
            'title' => '运行正常',
            'value' => 70,
            'color' => '#28bcfe'
        ],
        [
            'title' => '运行流畅',
            'value' => 0,
            'color' => '#28bcfe'
        ],
        [
            'title' => '未知',
            'value' => -1,
            'color' => '#ed4014'
        ],
    ];

    foreach ($array as $value) {
        if ($percent >= $value['value']) {
            return $value;
        }
    }

    return end($array);
}
