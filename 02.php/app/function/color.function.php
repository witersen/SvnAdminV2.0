<?php

function FunGetColor($percent)
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
    ];

    foreach ($array as $value) {
        if ($percent >= $value['value']) {
            return $value;
        }
    }
}
