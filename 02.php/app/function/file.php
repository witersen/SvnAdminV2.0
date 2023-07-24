<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * 获取文件夹体积
 * 
 * 使用php的内置方法
 */
function funGetDirSize($dir)
{
    clearstatcache();
    $dh = opendir($dir) or exit('打开目录错误'); //打开目录，返回一个目录流
    $size = 0;      //初始大小为0 
    while (false !== ($file = @readdir($dh))) { //循环读取目录下的文件
        if ($file != '.' and $file != '..') {
            $path = $dir . '/' . $file; //设置目录，用于含有子目录的情况
            if (is_dir($path)) {
                $size += funGetDirSize($path); //递归调用，计算目录大小
            } elseif (is_file($path)) {
                $size += filesize($path); //计算文件大小
            }
        }
    }
    closedir($dh); //关闭目录流
    return $size; //返回大小
}

/**
 * 传入以Byte为单位的值
 * 根据大小返回带有单位的值
 */
function funFormatSize($size)
{
    $size = empty($size) ? 0 : $size;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * 获取文件夹体积
 * 
 * 使用Linux命令
 * 
 * 返回byte
 */
function funGetDirSizeDu($path)
{
    $cmd = sprintf("du -s '%s' | awk '{print $1}'", $path);
    $result =  funShellExec($cmd);
    $result = $result['result'];
    $result  = (int)trim($result) * 1024;
    return $result;
}

/**
 * 获取文件夹下的文件列表
 * 
 * 文件名
 * 文件体积
 * 最后修改时间
 */
function funGetDirFileList($path)
{
    $filename = scandir($path);

    $fileArray = [];

    foreach ($filename as $key => $value) {
        if ($value == '.' || $value == '..' || is_dir($path . '/' . $value)) {
            continue;
        }
        array_push($fileArray, [
            'fileName' => $value,
            'fileSize' => funFormatSize(filesize($path . '/' . $value)),
            'fileEditTime' => date('Y-m-d H:i:s', (int)filemtime($path . '/' . $value))
        ]);
    }

    return $fileArray;
}
