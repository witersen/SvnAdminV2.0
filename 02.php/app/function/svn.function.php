<?php

function GetRepList()
{
    $repArray = array();
    $file_arr = scandir(SVN_REPOSITORY_PATH);
    foreach ($file_arr as $file_item) {
        if ($file_item != '.' && $file_item != '..') {
            if (is_dir(SVN_REPOSITORY_PATH . '/' . $file_item)) {
                $file_arr2 = scandir(SVN_REPOSITORY_PATH . '/' . $file_item);
                foreach ($file_arr2 as $file_item2) {
                    if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                        array_push($repArray, array(
                            'repository_name' => $file_item,
                            'repository_url' => SVN_REPOSITORY_PATH . '/' . $file_item,
                            'repository_size' => round(GetDirSize(SVN_REPOSITORY_PATH . '/' . $file_item) / (1024 * 1024), 2),
                            'repository_checkout_url' => 'svn://' . SERVER_DOMAIN . '/' . $file_item,
                        ));
                        break;
                    }
                }
            }
        }
    }
    return $repArray;
}

function GetSimpleRepList()
{
    $repArray = array();
    $file_arr = scandir(SVN_REPOSITORY_PATH);
    foreach ($file_arr as $file_item) {
        if ($file_item != '.' && $file_item != '..') {
            if (is_dir(SVN_REPOSITORY_PATH . '/' . $file_item)) {
                $file_arr2 = scandir(SVN_REPOSITORY_PATH . '/' . $file_item);
                foreach ($file_arr2 as $file_item2) {
                    if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                        array_push($repArray, $file_item);
                        break;
                    }
                }
            }
        }
    }
    return $repArray;
}
