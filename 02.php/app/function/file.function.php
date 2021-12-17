<?php

function GetDirSize($dir)
{
    clearstatcache();
    $dh = opendir($dir) or exit('打开目录错误'); //打开目录，返回一个目录流
    $size = 0;      //初始大小为0 
    while (false !== ($file = @readdir($dh))) { //循环读取目录下的文件
        if ($file != '.' and $file != '..') {
            $path = $dir . '/' . $file; //设置目录，用于含有子目录的情况
            if (is_dir($path)) {
                $size += GetDirSize($path); //递归调用，计算目录大小
            } elseif (is_file($path)) {
                $size += filesize($path); //计算文件大小
            }
        }
    }
    closedir($dh); //关闭目录流
    return $size; //返回大小
}
