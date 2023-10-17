<?php

//不使用时可取消注释以下信息以方式其它人访问到此信息
// if (!preg_match('/cli/i', php_sapi_name())) {
//     exit('require php-cli mode');
// }

$require_functions = ['shell_exec'];
$disable_functions = explode(',', ini_get('disable_functions'));
foreach ($disable_functions as $disable) {
    if (in_array(trim($disable), $require_functions)) {
        exit("需要的 $disable 函数被禁用");
    }
}

print_r(shell_exec('id -a'));
