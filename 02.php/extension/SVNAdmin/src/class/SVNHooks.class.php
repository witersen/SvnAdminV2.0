<?php
/*
 * @Author: witersen
 * @Date: 2022-04-27 17:53:46
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-28 01:21:07
 * @Description: QQ:1801168257
 * @copyright: https://github.com/witersen/
 */

namespace SVNAdmin\SVN;

class Hooks extends Core
{
    function __construct($authzFileContent, $passwdFileContent)
    {
        parent::__construct($authzFileContent, $passwdFileContent);
    }
}
