<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Ldap extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
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
     * 过滤ldap用户
     *
     * @param \LDAP\Connection $connection
     * @param array|string $baseDn 用户所在的dn目录
     * @param array|string $filter 过滤条件
     * @param array $attributes 获取哪些字段 可以不填
     * @return array
     */
    private function GeteObjectFromEntry($connection, $baseDn, $filter, $attributes)
    {
        //每次取出500条
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

        //释放
        ldap_unbind($connection);

        return $data;
    }

    /**
     * 过滤ldap分组
     *
     * @param \LDAP\Connection $connection
     * @param array|string $baseDn 用户所在的dn目录
     * @param array|string $filter 过滤条件
     * @param array $attributes 获取哪些字段 可以不填
     * @param array $groupsToUserAttribute
     * @param array $groupsToUserAttribute
     * @return void
     */
    private function GetLdapGroups($connection, $baseDn, $filter, $attributes, $groupsToUserAttribute, $groupsToUserAttributeValue)
    {
        //每次取出500条
        $pageSize = 500;
        $pageCookie = "";
        $data = [];
        do {
            if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
                ldap_control_paged_result($connection, $pageSize, true, $pageCookie);
            }

            $result = ldap_search($connection, $baseDn, $filter, $attributes, 0, 0);
            if (!$result) {
                break;
            }

            $entries = ldap_get_entries($connection, $result);
            if (!$entries) {
                break;
            }

            $count = $entries["count"];
            for ($i = 0; $i < $count; ++$i) {
                // A $entry (array) contains all attributes of a single dataset from LDAP.
                $entry = $entries[$i];

                // Create a new object which will hold the attributes.
                // And add the default attribute "dn".
                $o = self::createObjectFromEntry($entry);
                $data[] = $o;
            }

            if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
                ldap_control_paged_result_response($connection, $result, $pageCookie);
            }
        } while ($pageCookie !== null && $pageCookie != "");
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
}
