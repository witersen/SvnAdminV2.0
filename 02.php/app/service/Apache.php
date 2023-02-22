<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Svn as ServiceSvn;

class Apache extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $ServiceSvn;

    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取 apache 服务器信息
     *
     * @return void
     */
    public function GetApacheInfo()
    {
        return message(200, 1, '成功', [
            'enable' => $this->enableCheckout == 'http',

            'version' => funShellExec(sprintf("'%s' -v", $this->configBin['httpd']))['result'],
            'modules' => implode(',', explode("\n", trim(funShellExec(sprintf("ls '%s' | grep svn", $this->configSvn['apache_modules_path']))['result']))),
            'modules_path' => $this->configSvn['apache_modules_path'],
            'port' => $this->localHttpPort,
            'prefix' => $this->httpPrefix,
            'password_db' => $this->configSvn['http_passwd_file'],

            'user_source' => $this->httpDataSource['user_source'],
            'group_source' => $this->httpDataSource['group_source'],

            'ldap' => $this->httpDataSource['ldap']
        ]);
    }

    /**
     * 写入 subversion.conf 配置文件
     *
     * @return void
     */
    private function UpdConfSvn($prefix = '')
    {
        $prefix = empty($prefix) ? $this->httpPrefix : $prefix;

        $dataSource = $this->httpDataSource;
        $ldap = $dataSource['ldap'];

        if ($dataSource['user_source'] == 'httpPasswd') {
            $templeteSubversionPath = BASE_PATH . '/templete/apache/subversion.conf';
            if (!is_readable($templeteSubversionPath)) {
                return message(200, 0, sprintf('文件[%s]不可读或不存在', $templeteSubversionPath));
            }

            $DAV = 'svn';
            $SVNListParentPath = 'on';
            $SVNParentPath = $this->configSvn['rep_base_path'];
            $AuthType = 'Basic';
            $AuthName = "SVN Repo";
            $AuthUserFile = $this->configSvn['http_passwd_file'];
            $AuthzSVNAccessFile = $this->configSvn['svn_authz_file'];
            $Require = 'valid-user';

            $subversion = sprintf(
                file_get_contents($templeteSubversionPath),
                $prefix,
                $DAV,
                $SVNListParentPath,
                $SVNParentPath,
                $AuthType,
                $AuthName,
                $AuthUserFile,
                $AuthzSVNAccessFile,
                $Require
            );

            $subversionPath = $this->configSvn['apache_subversion_file'];
            if (!file_exists($subversionPath)) {
                @file_put_contents($subversionPath, $subversion);
                if (!file_exists($subversionPath)) {
                    funFilePutContents($subversionPath, $subversion, true);
                    if (!file_exists($subversionPath)) {
                        return message(200, 0, sprintf('无法创建文件[%s]', $subversionPath));
                    }
                }
            }

            if (!is_writeable($subversionPath)) {
                funFilePutContents($subversionPath, $subversion, true);
                if (!is_writeable($subversionPath)) {
                    return message(200, 0, sprintf('无法写入文件[%s]', $subversionPath));
                }
            }

            file_put_contents($subversionPath, $subversion);

            return message();
        } else {
            $templeteSubversionPath = BASE_PATH . '/templete/apache/subversion-ldap.conf';
            if (!is_readable($templeteSubversionPath)) {
                return message(200, 0, sprintf('文件[%s]不可读或不存在', $templeteSubversionPath));
            }

            $DAV = 'svn';
            $SVNListParentPath = 'on';
            $SVNParentPath = $this->configSvn['rep_base_path'];
            $AuthType = 'Basic';
            $AuthName = "SVN Repo";
            $AuthBasicProvider = 'ldap';
            $AuthLDAPBindAuthoritative = 'off';
            $AuthUserFile = $this->configSvn['http_passwd_file'];
            $AuthzSVNAccessFile = $this->configSvn['svn_authz_file'];
            $Require = 'ldap-user';

            //base sub on
            $scope = 'sub';
            $attributes = explode(',', $ldap['user_attributes']);
            if (substr($ldap['user_search_filter'], 0, 1) == '(' && substr($ldap['user_search_filter'], -1) == ')') {
                $filter = $ldap['user_search_filter'];
            } else {
                $filter = '(' . $ldap['user_search_filter'] . ')';
            }
            $AuthLDAPURL = rtrim(trim($ldap['ldap_host']), '/') . ':' . $ldap['ldap_port'] . '/' . $ldap['user_base_dn'] . '?' . $attributes[0] . '?' . $scope . '?'  . $filter;

            $AuthLDAPBindDN = $ldap['ldap_bind_dn'];
            $AuthLDAPBindPassword = $ldap['ldap_bind_password'];

            $subversion = sprintf(
                file_get_contents($templeteSubversionPath),
                $prefix,
                $DAV,
                $SVNListParentPath,
                $SVNParentPath,
                $AuthType,
                $AuthName,
                $AuthBasicProvider,
                $AuthLDAPBindAuthoritative,
                $AuthUserFile,
                $AuthzSVNAccessFile,
                $Require,
                $AuthLDAPURL,
                $AuthLDAPBindDN,
                $AuthLDAPBindPassword
            );

            $subversionPath = $this->configSvn['apache_subversion_file'];
            if (!file_exists($subversionPath)) {
                @file_put_contents($subversionPath, $subversion);
                if (!file_exists($subversionPath)) {
                    funFilePutContents($subversionPath, $subversion, true);
                    if (!file_exists($subversionPath)) {
                        return message(200, 0, sprintf('无法创建文件[%s]', $subversionPath));
                    }
                }
            }

            if (!is_writeable($subversionPath)) {
                funFilePutContents($subversionPath, $subversion, true);
                if (!is_writeable($subversionPath)) {
                    return message(200, 0, sprintf('无法写入文件[%s]', $subversionPath));
                }
            }

            file_put_contents($subversionPath, $subversion);

            return message();
        }
    }

    /**
     * 启用 http 协议检出
     *
     * @return void
     */
    public function UpdSubversionEnable()
    {
        //写入 subversion.conf
        $result = $this->UpdConfSvn();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        //清空数据库的用户
        $this->database->delete('svn_users', [
            'svn_user_id[>]' => 0
        ]);

        //切换目前状态
        $this->database->update('options', [
            'option_value' => 'http',
        ], [
            'option_name' => '24_enable_checkout',
        ]);

        //禁用 svn 协议检出
        (new ServiceSvn())->UpdSvnDisable();

        //重启 httpd
        funShellExec(sprintf("'%s' -k graceful", $this->configBin['httpd']), true);

        return message();
    }

    /**
     * 禁用 http 协议检出
     *
     * @return void
     */
    public function UpdSubversionDisable()
    {
        funShellExec(sprintf("rm -f '%s'", $this->configSvn['apache_subversion_file']), true);

        clearstatcache();

        if (file_exists($this->configSvn['apache_subversion_file'])) {
            return message(200, 0, sprintf("无法移除文件[%s]", $this->configSvn['apache_subversion_file']));
        }

        return message();
    }

    /**
     * 用户登录认证
     *
     * @return void
     */
    public function Auth($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -bv '%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username,
                $password
            )
        );
        // if ($result['code'] != 0) {
        //     return message(200, 0, $result['error']);
        // }

        if (substr(trim($result['error']), -8) == 'correct.') {
            return message();
        }

        return message(200, 0, '登录失败[账号或密码错误]');
    }

    /**
     * 创建用户
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function CreateUser($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -b '%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username,
                $password
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message();
    }

    /**
     * 更新用户密码
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function UpdUserPass($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -b '%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username,
                $password
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message();
    }

    /**
     * 删除用户
     *
     * @param string $username
     * @return void
     */
    public function DelUser($username)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -D '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message();
    }

    /**
     * 生成用户账户密码字符串
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function CreateUserStr($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $username,
                $password
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message(200, 1, '成功', $result['result']);
    }

    /**
     * 修改 http 协议显示端口
     *
     * @return void
     */
    public function UpdHttpPort()
    {
        if ($this->enableCheckout == 'svn') {
            return message(200, 0, '需要切换为http协议检出状态才可修改');
        }

        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'port' => ['type' => 'integer', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $this->database->update('options', [
            'option_value' => serialize([
                'local_http_port' => $this->payload['port']
            ])
        ], [
            'option_name' => '24_local_host'
        ]);

        return message();
    }

    /**
     * 修改 http 协议访问前缀
     *
     * @return void
     */
    public function UpdHttpPrefix()
    {
        if ($this->enableCheckout == 'svn') {
            return message(200, 0, '需要切换为http协议检出状态才可修改');
        }

        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'prefix' => ['type' => 'string', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        if (substr($this->payload['prefix'], 0, 1) != '/') {
            return message(200, 0, '前缀要携带/');
        }

        $this->database->update('options', [
            'option_value' => $this->payload['prefix'],
        ], [
            'option_name' => '24_http_prefix',
        ]);

        //写入 subversion.conf
        $result = $this->UpdConfSvn($this->payload['prefix']);
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        //重启 httpd
        funShellExec(sprintf("'%s' -k graceful", $this->configBin['httpd']), true);

        return message();
    }

    /**
     * 保存 apache 相关配置
     *
     * @return void
     */
    public function UpdHttpUsersource()
    {
        $checkResult = funCheckForm($this->payload, [
            'data_source' => ['type' => 'array', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $dataSource = $this->payload['data_source'];

        if ($dataSource['user_source'] == 'ldap') {

            if (substr($dataSource['ldap']['ldap_host'], 0, strlen('ldap://')) != 'ldap://' && substr($dataSource['ldap']['ldap_host'], 0, strlen('ldaps://')) != 'ldaps://') {
                return message(200, 0, 'ldap主机名必须以 ldap:// 或者 ldaps:// 开始');
            }

            if (preg_match('/\:[0-9]+/', $dataSource['ldap']['ldap_host'], $matches)) {
                return message(200, 0, 'ldap主机名不可携带端口');
            }

            if ($dataSource['group_source'] == 'ldap') {
                $checkResult = funCheckForm($dataSource['ldap'], [
                    'ldap_host' => ['type' => 'string', 'notNull' => true],
                    'ldap_port' => ['type' => 'integer'],
                    'ldap_version' => ['type' => 'integer'],
                    'ldap_bind_dn' => ['type' => 'string', 'notNull' => true],
                    'ldap_bind_password' => ['type' => 'string', 'notNull' => true],

                    'group_base_dn' => ['type' => 'string', 'notNull' => true],
                    'group_search_filter' => ['type' => 'string', 'notNull' => true],
                    'group_attributes' => ['type' => 'string', 'notNull' => true],
                    'groups_to_user_attribute' => ['type' => 'string', 'notNull' => true],
                    'groups_to_user_attribute_value' => ['type' => 'string', 'notNull' => true],
                ]);
                if ($checkResult['status'] == 0) {
                    return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
                }
            } else {
                $checkResult = funCheckForm($dataSource['ldap'], [
                    'ldap_host' => ['type' => 'string', 'notNull' => true],
                    'ldap_port' => ['type' => 'integer'],
                    'ldap_version' => ['type' => 'integer'],
                    'ldap_bind_dn' => ['type' => 'string', 'notNull' => true],
                    'ldap_bind_password' => ['type' => 'string', 'notNull' => true],

                    'user_base_dn' => ['type' => 'string', 'notNull' => true],
                    'user_search_filter' => ['type' => 'string', 'notNull' => true],
                    'user_attributes' => ['type' => 'string', 'notNull' => true],
                ]);
                if ($checkResult['status'] == 0) {
                    return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
                }
            }

            //清空数据库的用户
            $this->database->delete('svn_users', [
                'svn_user_id[>]' => 0
            ]);

            if ($dataSource['group_source'] == 'ldap') {
                //清空数据库的分组
                $this->database->delete('svn_groups', [
                    'svn_group_id[>]' => 0
                ]);
            }

            $this->database->update('options', [
                'option_value' => serialize([
                    'user_source' => $dataSource['user_source'],
                    'group_source' => $dataSource['group_source'],
                    'ldap' => $dataSource['ldap']
                ])
            ], [
                'option_name' => '24_http_datasource'
            ]);
        } else {
            $this->database->update('options', [
                'option_value' => serialize([
                    'user_source' => 'httpPasswd',
                    'group_source' => 'authz',
                    'ldap' => $dataSource['ldap']
                ])
            ], [
                'option_name' => '24_http_datasource'
            ]);
        }

        parent::ReloadDatasource();

        $result = $this->UpdConfSvn();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        //重启 httpd
        funShellExec(sprintf("'%s' -k graceful", $this->configBin['httpd']), true);

        return message();
    }
}
