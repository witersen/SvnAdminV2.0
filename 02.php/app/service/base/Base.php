<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

//require config
auto_require(BASE_PATH . '/config/');

//require function
auto_require(BASE_PATH . '/app/function/');

//require util
auto_require(BASE_PATH . '/app/util/');

//require service
auto_require(BASE_PATH . '/app/service/');

//require extension
auto_require(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php');

auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/Exception.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/PHPMailer.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/SMTP.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/language/phpmailer.lang-zh_cn.php');

auto_require(BASE_PATH . '/extension/Verifycode/Verifycode.php');

auto_require(BASE_PATH . '/extension/Witersen/SVNAdmin.php');

function auto_require($path, $recursively = false)
{
    if (is_file($path)) {
        if (substr($path, -4) == '.php') {
            require_once $path;
        }
    } else {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $recursively ? auto_require($path . '/' . $file, true) : '';
                } else {
                    if (substr($file, -4) == '.php') {
                        require_once $path . '/' . $file;
                    }
                }
            }
        }
    }
}

use Check;

use Config;

use Medoo\Medoo;

use Witersen\SVNAdmin;
use SVNAdmin\SVN\Group;
use SVNAdmin\SVN\Rep;
use SVNAdmin\SVN\User;

class Base
{
    public $token;

    //根据token得到的用户信息
    public $userName;
    public $userRoleId;

    //svn配置文件
    public $authzContent;
    public $passwdContent;

    //medoo
    public $database;

    //配置信息
    public $configBin;
    public $configSvn;
    public $configReg;
    public $configSign;

    //payload
    public $payload;

    //SVNAdmin
    public $SVNAdmin;
    public $SVNAdminGroup;
    public $SVNAdminInfo;
    public $SVNAdminRep;
    public $SVNAdminUser;

    //检查
    public $checkService;

    /**
     * 子管理员权限树
     *
     * @var array
     */
    public $subadminTree = [
        [
            'title' => '信息统计',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'index',
            'necessary_functions' => [
                'Statistics/GetLoadInfo',
                'Statistics/GetDiskInfo',
                'Statistics/GetStatisticsInfo',
            ],
            'children' => []
        ],
        [
            'title' => 'SVN仓库',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'repositoryInfo',
            'necessary_functions' => [
                'Svnrep/GetRepList',
                'Svnrep/GetSvnserveStatus',
            ],
            'children' => [
                [
                    'title' => '新建仓库',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnrep/CreateRep',
                    ],
                    'children' => []
                ],
                [
                    'title' => 'authz检测',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnrep/CheckAuthz',
                    ],
                    'children' => []
                ],
                [
                    'title' => '备注信息修改',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnrep/UpdRepNote',
                    ],
                    'children' => []
                ],
                [
                    'title' => '仓库内容浏览',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnrep/GetCheckout',
                        'Svnrep/GetRepCon',
                    ],
                    'children' => []
                ],
                [
                    'title' => '仓库备份管理',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [],
                    'children' => [
                        [
                            'title' => '获取备份文件列表',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/GetBackupList',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '生成仓库备份文件(svnadmin dump)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/SvnadminDump',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '删除仓库备份文件',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/DelRepBackup',
                            ],
                            'children' => []
                        ],
                    ]
                ],
                [
                    'title' => '仓库权限配置',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnrep/GetRepTree',
                        'Svnrep/GetRepPathAllPri',
                        'Svnrep/DelRepBackup',
                    ],
                    'children' => [
                        [
                            'title' => '仓库授权组件',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [],
                            'children' => [
                                [
                                    'title' => '仓库目录树浏览(左侧)',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [],
                                    'children' => [
                                        [
                                            'title' => '获取仓库目录树',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/GetRepTree',
                                            ],
                                            'children' => []
                                        ],
                                    ]
                                ],
                                [
                                    'title' => '仓库路径授权(右侧)',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [],
                                    'children' => [
                                        [
                                            'title' => '增加某个仓库路径下的权限',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/CreateRepPathPri',
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '获取某个仓库路径下的权限',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/GetRepPathAllPri',
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '修改某个仓库路径下的权限',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/UpdRepPathPri',
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '删除某个仓库路径下的权限',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/DelRepPathPri',
                                            ],
                                            'children' => []
                                        ],
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => '对象列表组件',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [],
                            'children' => [
                                [
                                    'title' => '获取SVN用户列表',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svnuser/GetUserList',
                                    ],
                                    'children' => []
                                ],
                                [
                                    'title' => '获取SVN分组列表',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svngroup/GetGroupList',
                                    ],
                                    'children' => [
                                        [
                                            'title' => '获取SVN分组成员',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svngroup/GetGroupMember',
                                            ],
                                            'children' => []
                                        ],
                                    ]
                                ],
                                [
                                    'title' => '获取SVN别名列表',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svnaliase/GetAliaseList',
                                    ],
                                    'children' => []
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => '仓库钩子编辑',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [],
                    'children' => [
                        [
                            'title' => '获取仓库钩子列表',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/GetRepHooks'
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '获取常用钩子列表',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/GetRecommendHooks'
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '修改仓库钩子内容',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/UpdRepHook'
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '清空仓库钩子内容',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svnrep/DelRepHook'
                            ],
                            'children' => []
                        ],
                    ]
                ],
                [
                    'title' => '其它',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [],
                    'children' => [
                        [
                            'title' => '高级',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'necessary_functions' => [],
                            'children' => [
                                [
                                    'title' => '属性(仓库详情)',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svnrep/GetRepHooks'
                                    ],
                                    'children' => []
                                ],
                                [
                                    'title' => '恢复(仓库备份恢复)',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svnrep/GetRepHooks'
                                    ],
                                    'children' => [
                                        [
                                            'title' => '获取当前上传限制',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/GetUploadLimit'
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '获取备份文件列表',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/GetBackupList'
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '上传文件(上传备份文件到服务器)',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/UploadBackup'
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '导入仓库备份(svnadmin load)',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svnrep/SvnadminLoad'
                                            ],
                                            'children' => []
                                        ],
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => '修改(修改仓库名称)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'necessary_functions' => [
                                'Svnrep/UpdRepName'
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '删除(删除仓库)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'necessary_functions' => [
                                'Svnrep/DelRep'
                            ],
                            'children' => []
                        ],
                    ]
                ],
            ]
        ],
        [
            'title' => 'SVN用户',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'repositoryUser',
            'necessary_functions' => [
                'Svnuser/GetUserList',
            ],
            'children' => [
                [
                    'title' => '新建SVN用户',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnuser/CreateUser',
                    ],
                    'children' => []
                ],
                [
                    'title' => '弃用或禁用SVN用户',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnuser/UpdUserStatus',
                    ],
                    'children' => []
                ],
                [
                    'title' => '修改SVN用户备注信息',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnuser/UpdUserNote',
                    ],
                    'children' => []
                ],
                [
                    'title' => '有权路径',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [],
                    'children' => [
                        [
                            'title' => '查看',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'necessary_functions' => [
                                'Svnuser/GetSvnUserRepList2',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '二次授权',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'necessary_functions' => [],
                            'children' => [
                                [
                                    'title' => '二次授权状态',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Secondpri/UpdSecondpri',
                                    ],
                                    'children' => []
                                ],
                                [
                                    'title' => '二次授权对象',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Secondpri/GetSecondpriObjectList',
                                    ],
                                    'children' => [
                                        [
                                            'title' => '添加成员',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Secondpri/CreateSecondpriObject',
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '移除成员',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Secondpri/DelSecondpriObject',
                                            ],
                                            'children' => []
                                        ],
                                        [
                                            'title' => '对象列表组件',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [],
                                            'children' => [
                                                [
                                                    'title' => '获取SVN用户列表',
                                                    'expand' => false,
                                                    'checked' => false,
                                                    'disabled' => true,
                                                    'necessary_functions' => [
                                                        'Svnuser/GetUserList',
                                                    ],
                                                    'children' => []
                                                ],
                                                [
                                                    'title' => '获取SVN分组列表',
                                                    'expand' => false,
                                                    'checked' => false,
                                                    'disabled' => true,
                                                    'necessary_functions' => [
                                                        'Svngroup/GetGroupList',
                                                    ],
                                                    'children' => [
                                                        [
                                                            'title' => '获取SVN分组成员',
                                                            'expand' => false,
                                                            'checked' => false,
                                                            'disabled' => true,
                                                            'necessary_functions' => [
                                                                'Svngroup/GetGroupMember',
                                                            ],
                                                            'children' => []
                                                        ],
                                                    ]
                                                ],
                                                [
                                                    'title' => '获取SVN别名列表',
                                                    'expand' => false,
                                                    'checked' => false,
                                                    'disabled' => true,
                                                    'necessary_functions' => [
                                                        'Svnaliase/GetAliaseList',
                                                    ],
                                                    'children' => []
                                                ],
                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ],
                    ]
                ],
                [
                    'title' => '修改SVN用户密码',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnuser/UpdUserPass',
                    ],
                    'children' => []
                ],
                [
                    'title' => '删除SVN用户',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svnuser/DelUser',
                    ],
                    'children' => []
                ],
            ]
        ],
        [
            'title' => 'SVN分组',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'repositoryGroup',
            'necessary_functions' => [],
            'children' => [
                [
                    'title' => '新建SVN分组',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svngroup/CreateGroup',
                    ],
                    'children' => []
                ],
                [
                    'title' => '备注信息',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svngroup/UpdGroupNote',
                    ],
                    'children' => []
                ],
                [
                    'title' => '成员',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svngroup/CreateGroup',
                    ],
                    'children' => [
                        [
                            'title' => '获取分组成员列表',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svngroup/CreateGroup',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '添加或删除分组成员',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [
                                'Svngroup/UpdGroupMember',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '对象列表组件',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => true,
                            'necessary_functions' => [],
                            'children' => [
                                [
                                    'title' => '获取SVN用户列表',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svnuser/GetUserList',
                                    ],
                                    'children' => []
                                ],
                                [
                                    'title' => '获取SVN分组列表',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svngroup/GetGroupList',
                                    ],
                                    'children' => [
                                        [
                                            'title' => '获取SVN分组成员',
                                            'expand' => false,
                                            'checked' => false,
                                            'disabled' => true,
                                            'necessary_functions' => [
                                                'Svngroup/GetGroupMember',
                                            ],
                                            'children' => []
                                        ],
                                    ]
                                ],
                                [
                                    'title' => '获取SVN别名列表',
                                    'expand' => false,
                                    'checked' => false,
                                    'disabled' => true,
                                    'necessary_functions' => [
                                        'Svnaliase/GetAliaseList',
                                    ],
                                    'children' => []
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => '编辑',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svngroup/UpdGroupName',
                    ],
                    'children' => []
                ],
                [
                    'title' => '删除',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'necessary_functions' => [
                        'Svngroup/DelGroup',
                    ],
                    'children' => []
                ],
            ]
        ],
        [
            'title' => '系统日志',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'logs',
            'necessary_functions' => [],
            'children' => [
                [
                    'title' => '获取日志列表',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'router_name' => 'Logs',
                    'necessary_functions' => [
                        'Logs/GetLogList',
                    ],
                    'children' => []
                ],
                [
                    'title' => '清空日志',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'router_name' => 'Logs',
                    'necessary_functions' => [
                        'Logs/DelLogs',
                    ],
                    'children' => []
                ],
            ]
        ],
        [
            'title' => '任务计划',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'crond',
            'necessary_functions' => [
                'Crond/GetCronStatus',
                'Crond/GetCrontabList',
                'Crond/GetRepList'
            ],
            'children' => [
                [
                    'title' => '添加任务计划',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'router_name' => 'Crond',
                    'necessary_functions' => [
                        'Crond/CreateCrontab',
                    ],
                    'children' => []
                ],
                [
                    'title' => '启用用或禁用任务计划',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'router_name' => 'Crond',
                    'necessary_functions' => [
                        'Crond/UpdCrondStatus',
                    ],
                    'children' => []
                ],
                [
                    'title' => '其它',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => false,
                    'router_name' => 'Crond',
                    'necessary_functions' => [],
                    'children' => [
                        [
                            'title' => '日志(查看任务计划执行日志)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'router_name' => 'Crond',
                            'necessary_functions' => [
                                'Crond/GetCrontabLog',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '编辑(编辑任务计划)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'router_name' => 'Crond',
                            'necessary_functions' => [
                                'Crond/GetCrontabLog',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '删除(删除任务计划)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'router_name' => 'Crond',
                            'necessary_functions' => [
                                'Crond/DelCrontab',
                            ],
                            'children' => []
                        ],
                        [
                            'title' => '执行(立即执行一次任务计划)',
                            'expand' => false,
                            'checked' => false,
                            'disabled' => false,
                            'router_name' => 'Crond',
                            'necessary_functions' => [
                                'Crond/TriggerCrontab',
                            ],
                            'children' => []
                        ],
                    ]
                ],
            ]
        ],
        [
            'title' => '个人中心',
            'expand' => false,
            'checked' => true,
            'disabled' => true,
            'router_name' => 'personal',
            'necessary_functions' => [
                'Personal/UpdSubadminUserPass'
            ],
            'children' => []
        ],
        [
            'title' => '系统配置',
            'expand' => false,
            'checked' => false,
            'disabled' => false,
            'router_name' => 'setting',
            'necessary_functions' => [],
            'children' => [
                [
                    'title' => 'Subversion',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => true,
                    'necessary_functions' => [
                        'Setting/GetSvnserveInfo',
                        'Setting/StopSvnserve',
                        'Setting/StartSvnserve',
                        'Setting/UpdSvnservePort',
                        'Setting/UpdSvnserveHost',
                        'Setting/UpdManageHost',
                        'Setting/UpdCheckoutHost',
                    ],
                    'children' => []
                ],
                [
                    'title' => '路径信息',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => true,
                    'necessary_functions' => [
                        'Setting/GetDirInfo'
                    ],
                    'children' => []
                ],
                [
                    'title' => '邮件服务',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => true,
                    'necessary_functions' => [
                        'Setting/SendMailTest',
                        'Setting/UpdMailInfo'
                    ],
                    'children' => []
                ],
                [
                    'title' => '消息推送',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => true,
                    'necessary_functions' => [
                        'Setting/UpdPushInfo'
                    ],
                    'children' => []
                ],
                [
                    'title' => '安全配置',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => true,
                    'necessary_functions' => [
                        'Setting/UpdSafeInfo'
                    ],
                    'children' => []
                ],
                [
                    'title' => '系统更新',
                    'expand' => false,
                    'checked' => false,
                    'disabled' => true,
                    'necessary_functions' => [
                        'Setting/CheckUpdate'
                    ],
                    'children' => []
                ],
            ]
        ],
    ];

    function __construct($parm)
    {
        //配置信息
        $this->configBin =  Config::get('bin');                       //可执行文件路径
        $configDatabase = Config::get('database');              //数据库配置
        $this->configSvn = Config::get('svn');                        //仓库
        $this->configReg = Config::get('reg');                        //正则
        $this->configSign = Config::get('sign');                      //密钥

        $this->token = isset($parm['token']) ? $parm['token'] : '';

        /**
         * 4、用户信息获取
         */
        if (empty($this->token)) {
            $this->userRoleId = isset($parm['payload']['userRoleId']) ? $parm['payload']['userRoleId'] : 0;
            $this->userName = isset($parm['payload']['userName']) ? $parm['payload']['userName'] : 0;
        } else {
            $array = explode($this->configSign['signSeparator'], $this->token);
            $this->userRoleId = $array[0];
            $this->userName = $array[1];
        }

        /**
         * 6、获取数据库连接
         */
        if (array_key_exists('database_file', $configDatabase)) {
            $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $this->configSvn['home_path']);
        }
        $this->database = new Medoo($configDatabase);

        /**
         * 8、获取authz和passwd的配置文件信息
         */
        $this->authzContent = file_get_contents($this->configSvn['svn_authz_file']);
        $this->passwdContent = file_get_contents($this->configSvn['svn_passwd_file']);

        /**
         * 9、获取payload
         */
        $this->payload = isset($parm['payload']) ? $parm['payload'] : [];

        /**
         * 10、svnadmin对象
         */
        $this->SVNAdmin = new SVNAdmin();

        /**
         * 11、检查对象
         */
        $this->checkService = new Check($this->configReg);
    }

    /**
     * 获取动态路由
     */
    public function GetDynamicRouting($userRole)
    {
        $route = [
            'name' => 'manage',
            'path' => '/',
            'redirect' => [
                'name' => 'login'
            ],
            'meta' => [
                'title' => 'SVNAdmin',
                'requireAuth' => false,
            ],
            'component' => 'layout/basicLayout/index.vue',
            'children' => [
                [
                    'name' => 'index',
                    'path' => '/index',
                    'meta' => [
                        'title' => '信息统计',
                        'icon' => 'ios-stats',
                        'requireAuth' => true,
                        'user_role_id' => [1, 3],
                        'group' => [
                            'name' => '仓库',
                            'num' => 1
                        ],
                        'id' => 1001
                    ],
                    'component' => 'index/index.vue'
                ],
                [
                    'name' => 'repositoryInfo',
                    'path' => '/repositoryInfo',
                    'meta' => [
                        'title' => 'SVN仓库',
                        'icon' => 'logo-buffer',
                        'requireAuth' => true,
                        'user_role_id' => [1, 2, 3],
                        'group' => [
                            'name' => '仓库',
                            'num' => 1
                        ],
                        'id' => 1002
                    ],
                    'component' => 'repositoryInfo/index.vue'
                ],
                [
                    'name' => 'repositoryUser',
                    'path' => '/repositoryUser',
                    'meta' => [
                        'title' => 'SVN用户',
                        'icon' => 'md-person',
                        'requireAuth' => true,
                        'user_role_id' => [1, 3],
                        'group' => [
                            'name' => '仓库',
                            'num' => 1
                        ],
                        'id' => 1003
                    ],
                    'component' => 'repositoryUser/index.vue'
                ],
                [
                    'name' => 'repositoryGroup',
                    'path' => '/repositoryGroup',
                    'meta' => [
                        'title' => 'SVN分组',
                        'icon' => 'md-people',
                        'requireAuth' => true,
                        'user_role_id' => [1, 3],
                        'group' => [
                            'name' => '仓库',
                            'num' => 1
                        ],
                        'id' => 1004
                    ],
                    'component' => 'repositoryGroup/index.vue'
                ],
                [
                    'name' => 'logs',
                    'path' => '/logs',
                    'meta' => [
                        'title' => '系统日志',
                        'icon' => 'md-bug',
                        'requireAuth' => true,
                        'user_role_id' => [1, 3],
                        'group' => [
                            'name' => '运维',
                            'num' => 2
                        ],
                        'id' => 1005
                    ],
                    'component' => 'logs/index.vue'
                ],
                [
                    'name' => 'crond',
                    'path' => '/crond',
                    'meta' => [
                        'title' => '任务计划',
                        'icon' => 'ios-alarm',
                        'requireAuth' => true,
                        'user_role_id' => [1, 3],
                        'group' => [
                            'name' => '运维',
                            'num' => 2
                        ],
                        'id' => 1006
                    ],
                    'component' => 'crond/index.vue'
                ],
                [
                    'name' => 'personal',
                    'path' => '/personal',
                    'meta' => [
                        'title' => '个人中心',
                        'icon' => 'md-cube',
                        'requireAuth' => true,
                        'user_role_id' => [1, 2, 3],
                        'group' => [
                            'name' => '高级',
                            'num' => 3
                        ],
                        'id' => 1007
                    ],
                    'component' => 'personal/index.vue'
                ],
                [
                    'name' => 'subadmin',
                    'path' => '/subadmin',
                    'meta' => [
                        'title' => '子管理员',
                        'icon' => 'md-hand',
                        'requireAuth' => true,
                        'user_role_id' => [1],
                        'group' => [
                            'name' => '高级',
                            'num' => 3
                        ],
                        'id' => 1008
                    ],
                    'component' => 'subadmin/index.vue'
                ],
                [
                    'name' => 'setting',
                    'path' => '/setting',
                    'meta' => [
                        'title' => '系统配置',
                        'icon' => 'md-settings',
                        'requireAuth' => true,
                        'user_role_id' => [1, 3],
                        'group' => [
                            'name' => '高级',
                            'num' => 3
                        ],
                        'id' => 1009
                    ],
                    'component' => 'setting/index.vue'
                ],
            ]
        ];

        $functions = [];
        if ($userRole == 3) {
            //子管理员根据权限树过滤路由
            $routerNames = array_column($route['children'], 'name');
            $subadminTree = $this->database->get('subadmin', 'subadmin_tree', [
                'subadmin_name' => $this->userName
            ]);
            $subadminTree = json_decode($subadminTree, true);
            if (empty($subadminTree)) {
                $subadminTree = $this->subadminTree;
                $this->database->update('subadmin', [
                    'subadmin_tree' => json_encode($this->subadminTree)
                ], [
                    'subadmin_name' => $this->userName
                ]);
            }
            foreach ($subadminTree as $node) {
                $tempFunctions = [];
                if ($node['checked']) {
                    $tempFunctions = $node['necessary_functions'];
                }
                $tempFunctions = array_merge($tempFunctions, $this->GetPriFunctions($node['children']));
                if (empty($tempFunctions)) {
                    if (($index = array_search($node['router_name'], $routerNames)) !== false) {
                        unset($route['children'][$index]);
                    }
                    continue;
                }
                $functions = array_merge($functions, $tempFunctions);
            }
        }

        //根据meta值过滤路由(SVN用户)
        foreach ($route['children'] as $key => $value) {
            if (!in_array($userRole, $value['meta']['user_role_id'])) {
                unset($route['children'][$key]);
            }
        }

        $route['children'] = array_values($route['children']);

        return [
            'route' => $route,
            'functions' => $functions
        ];
    }

    /**
     * 获取有权函数
     */
    public function GetPriFunctions($tree)
    {
        if (empty($tree)) {
            return [];
        }

        $functions = [];
        foreach ($tree as $node) {
            if ($node['checked']) {
                $functions = array_merge($functions, $node['necessary_functions']);
                $functions = array_merge($functions, $this->GetPriFunctions($node['children']));
            }
        }

        return $functions;
    }
}
