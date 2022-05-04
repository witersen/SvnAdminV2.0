<?php
/*
 * @Author: witersen
 * @Date: 2022-05-03 21:06:50
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 01:09:40
 * @Description: QQ:1801168257
 */

function shellPassthru($shell)
{
    if (trim($shell) == '') {
        return  false;
    }

    $config_svnadmin_svn = config('svnadmin_svn');

    //定义错误输出文件路径
    $stderrFile = $config_svnadmin_svn['temp_base_path'] . uniqid();

    //将标准错误重定向到文件
    //使用状态码来标识错误信息
    ob_start();
    passthru($shell . " 2>$stderrFile", $resultCode);
    $buffer = ob_get_contents();
    ob_end_clean();

    //将错误信息和正确信息分类收集
    $result = [
        'resultCode' => $resultCode,
        'result' => trim($buffer),
        'error' => file_get_contents($stderrFile)
    ];

    //销毁文件
    unlink($stderrFile);

    return $result;
}
