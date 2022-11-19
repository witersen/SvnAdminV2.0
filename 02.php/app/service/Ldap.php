<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Sasl as ServiceSasl;

class Ldap extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSasl;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceSasl = new ServiceSasl();
    }

    /**
     * 测试连接ldap服务器
     *
     * @return void
     */
    public function LdapTest()
    {
        if (!function_exists('ldap_connect')) {
            return message(200, 0, '请先安装php的ldap依赖');
        }

        $checkResult = funCheckForm($this->payload, [
            'type' => ['type' => 'string', 'notNull' => true],
            'data_source' => ['type' => 'array', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $dataSource = $this->payload['data_source'];

        $type = $this->payload['type'];
        if (!in_array($type, ['connection', 'user', 'group'])) {
            return message(200, 0, '无效的验证类型');
        }

        $checkResult = funCheckForm($dataSource, [
            'ldap_host' => ['type' => 'string', 'notNull' => true],
            'ldap_port' => ['type' => 'integer'],
            'ldap_version' => ['type' => 'integer'],
            'ldap_bind_dn' => ['type' => 'string', 'notNull' => true],
            'ldap_bind_password' => ['type' => 'string', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $connection = ldap_connect($dataSource['ldap_host'], $dataSource['ldap_port']);
        if (!$connection) {
            return message(200, 0, '连接失败');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return message(200, 0, sprintf('连接失败: %s', ldap_error($connection)));
        }

        if ($type == 'connection') {
            return message();
        }

        if ($type == 'user') {
            $checkResult = funCheckForm($dataSource, [
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

            $users = $this->GeteObjectFromEntry($connection, $dataSource['user_base_dn'], $dataSource['user_search_filter'], [$dataSource['user_attributes']]);

            return message(200, 1, '成功', [
                'count' => count($users),
                'users' => implode(',', $users)
            ]);
        } else if ($type == 'group') {
            $checkResult = funCheckForm($dataSource, [
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

            $groups = $this->GeteObjectFromEntry($connection, $dataSource['group_base_dn'], $dataSource['group_search_filter'], [$dataSource['group_attributes']], $dataSource['groups_to_user_attribute'], $dataSource['groups_to_user_attribute_value']);

            return message(200, 1, '成功', [
                'count' => count($groups),
                'groups' => implode(',', $groups)
            ]);
        }
    }

    /**
     * 保存ldap配置信息
     *
     * @return void
     */
    public function UpdLdapInfo()
    {
        $checkResult = funCheckForm($this->payload, [
            'data_source' => ['type' => 'array', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $dataSource = $this->database->get('options', 'option_value', [
            'option_name' => 'data_source'
        ]);

        if (empty($dataSource)) {
            $this->database->insert('options', [
                'option_value' => serialize($this->payload['data_source']),
                'option_name' => 'data_source'
            ]);
        } else {
            $this->database->update('options', [
                'option_value' => serialize($this->payload['data_source']),
            ], [
                'option_name' => 'data_source'
            ]);
        }

        if ($this->payload['data_source']['user_source'] == 'ldap') {
            $this->database->delete('svn_users', [
                'svn_user_id[>]' => 0
            ]);
            if ($this->payload['data_source']['group_source'] == 'ldap') {
                //清空数据库的分组
                $this->database->delete('svn_groups', [
                    'svn_group_id[>]' => 0
                ]);
                //清空authz文件的分组
                $result = $this->SVNAdmin->ClearGroupSection($this->authzContent);
                if (is_numeric($result)) {
                    if ($result == 612) {
                        return message(200, 0, '文件格式错误(不存在[groups]标识)');
                    } else {
                        return message(200, 0, "错误码$result");
                    }
                }
                file_put_contents($this->configSvn['svn_authz_file'], $result);
                $this->authzContent = $result;
            }

            $result = $this->ServiceSasl->UpdSvnSaslStart();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }
        }

        return message();
    }

    /**
     * 获取ldap配置信息
     *
     * @return void
     */
    public function GetLdapInfo()
    {
        $dataSource = $this->database->get('options', 'option_value', [
            'option_name' => 'data_source'
        ]);

        $default = [
            //数据源
            'user_source' => 'ldap',
            'group_source' => 'ldap',

            //ldap服务器
            'ldap_host' => 'ldap://127.0.0.1/',
            'ldap_port' => 389,
            'ldap_version' => 3,
            'ldap_bind_dn' => '',
            'ldap_bind_password' => '',

            //用户相关
            'user_base_dn' => '',
            'user_search_filter' => '',
            'user_attributes' => '',

            //分组相关
            'group_base_dn' => '',
            'group_search_filter' => '',
            'group_attributes' => '',
            'groups_to_user_attribute' => '',
            'groups_to_user_attribute_value' => ''
        ];

        $dataSource = empty($dataSource) ? $default : unserialize($dataSource);

        return message(200, 1, '成功', $dataSource);
    }

    /**
     * 过滤ldap对象
     *
     * @param \LDAP\Connection $connection
     * @param array|string $baseDn 用户所在的dn目录
     * @param array|string $filter 过滤条件
     * @param array $attributes 获取哪些字段 可以不填
     * @return array
     */
    private function GeteObjectFromEntry($connection, $baseDn, $filter, $attributes, $groupsToUserAttribute = '', $groupsToUserAttributeValue = '')
    {
        $pageSize = 500;
        $pageCookie = '';
        $data = [];
        do {
            ldap_control_paged_result($connection, $pageSize, true, $pageCookie);

            $result = ldap_search($connection, $baseDn, $filter, $attributes, 0, 0);
            if (!$result) {
                break;
            }

            $entries = ldap_get_entries($connection, $result);
            if (!$entries) {
                break;
            }

            if (!empty($entries)) {
                // 利用yield 循环
                $yieldData = $this->createRange($entries["count"], $entries);
                foreach ($yieldData as $item) {
                    // array_push($data, [
                    //     'username' => isset($item["cn"][0]) ? $item["cn"][0] : ' ', //用户名
                    //     'email' => isset($item["email"][0]) ? $item["email"][0] : '', // 邮箱
                    //     'mobile' => isset($item["mobile"][0]) ? $item["mobile"][0] : '', // 手机号
                    //     'name' => isset($item["displayname"][0]) ? $item["displayname"][0] : '' // 真实姓名
                    // ]);
                    array_push($data, isset($item[$attributes[0]][0]) ? $item[$attributes[0]][0] : ' ');
                }
            }
            ldap_control_paged_result_response($connection, $result, $pageCookie);
        } while ($pageCookie !== null && $pageCookie != '');

        ldap_unbind($connection);

        return $data;
    }

    /**
     * yield 生成器 ， 为了减少内存占用
     */
    private function createRange($number, $data)
    {
        for ($i = 0; $i < $number; $i++) {
            yield $data[$i];
        }
    }

    /**
     * ldap用户登录
     *
     * @return void
     */
    private function LdapConnection()
    {
        $dataSource = [];

        $connection = ldap_connect($dataSource['ldap_host'], $dataSource['ldap_port']);
        if (!$connection) {
            return false;
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return false;
        }
    }

    /**
     * 获取ldap用户列表
     *
     * @return array
     */
    public function GetLdapUsers()
    {
        $dataSource = $this->GetLdapInfo()['data'];

        $connection = ldap_connect($dataSource['ldap_host'], $dataSource['ldap_port']);
        if (!$connection) {
            return message(200, 0, '连接失败');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return message(200, 0, sprintf('连接失败: %s', ldap_error($connection)));
        }

        return message(200, 1, '成功', $this->GeteObjectFromEntry($connection, $dataSource['user_base_dn'], $dataSource['user_search_filter'], [$dataSource['user_attributes']]));
    }

    /**
     * 获取ldap分组列表
     *
     * @return array
     */
    public function GetLdapGroups()
    {
        $dataSource = $this->GetLdapInfo()['data'];

        $connection = ldap_connect($dataSource['ldap_host'], $dataSource['ldap_port']);
        if (!$connection) {
            return message(200, 0, '连接失败');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return message(200, 0, sprintf('连接失败: %s', ldap_error($connection)));
        }

        return message(200, 1, '成功', $this->GeteObjectFromEntry($connection, $dataSource['group_base_dn'], $dataSource['group_search_filter'], [$dataSource['group_attributes']], $dataSource['groups_to_user_attribute'], $dataSource['groups_to_user_attribute_value']));
    }
}
