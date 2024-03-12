<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use stdClass;

class Ldap extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 输入一个字符串 确保不以 UTF-8 输出
     *
     * @param string $input_string
     * @return string
     */
    private function ensure_not_utf8($input_string)
    {
        if (mb_detect_encoding($input_string, 'UTF-8', true) === 'UTF-8') {
            return mb_convert_encoding($input_string, 'ISO-8859-1', 'UTF-8');
        }
        return $input_string;
    }

    /**
     * 输入一个字符串 确保以 UTF-8 输出
     *
     * @param string $input_string
     * @return string
     */
    private function ensure_utf8($input_string)
    {
        if (mb_detect_encoding($input_string, 'UTF-8', true) !== 'UTF-8') {
            return mb_convert_encoding($input_string, 'UTF-8', 'auto');
        }
        return $input_string;
    }

    /**
     * 对将要发送到 LDAP 服务器的所有DN和属性进行编码处理
     *
     * @param string $str
     *
     * @return string
     */
    private function prepareQueryString($str, $protocolVersion)
    {
        if ($protocolVersion >= 3) {
            $str = $this->ensure_utf8($str);
        } elseif ($protocolVersion <= 2) {
            $str = $this->ensure_not_utf8($str);
        }
        return $str;
    }

    /**
     * 处理从 LDAP 服务器接收的字符串
     *
     * @param string $str
     *
     * @return string
     */
    private function prepareResultString($str, $protocolVersion)
    {
        if ($protocolVersion >= 3) {
            $str = $this->ensure_utf8($str);
        } elseif ($protocolVersion <= 2) {
            $str = $this->ensure_utf8($str);
        }
        return $str;
    }

    /**
     * Searches for entries in the ldap.
     * 
     * Using PHP version < 5.4 will never return more than 1001 items.
     *
     * @param \LDAP\Connection $conn
     * @param string $protocolVersion
     * @param string $base_dn
     * @param string $search_filter
     * @param string $return_attributes
     * @param integer $pageSize
     * @return array of stdClass objects with property values defined by $return_attributes+"dn"
     */
    private function objectSearch($conn, $protocolVersion, $base_dn, $search_filter, $return_attributes, $pageSize = 100, $oid = '1.2.840.113556.1.4.319')
    {
        $current_version = PHP_VERSION;

        $range1 = '5.4.0';
        $range2 = '7.4.0';

        if (version_compare($current_version, $range1, '>=') && version_compare($current_version, $range2, '<')) {
            return $this->objectSearch_54_to_74($conn, $protocolVersion, $base_dn, $search_filter, $return_attributes, $pageSize = 100);
        } elseif (version_compare($current_version, $range2, '>=')) {
            return $this->objectSearch_74_to_80($conn, $protocolVersion, $base_dn, $search_filter, $return_attributes, $pageSize = 100, $oid = '1.2.840.113556.1.4.319');
        } else {
            return $this->objectSearch_74_to_80($conn, $protocolVersion, $base_dn, $search_filter, $return_attributes, $pageSize = 100, $oid = '1.2.840.113556.1.4.319');
        }
    }

    /**
     * [5.4   , 7.4.0)
     *
     * @param \LDAP\Connection $conn
     * @param string $protocolVersion
     * @param string $base_dn
     * @param string $search_filter
     * @param string $return_attributes
     * @param integer $pageSize
     * @return array of stdClass objects with property values defined by $return_attributes+"dn"
     */
    private function objectSearch_54_to_74($conn, $protocolVersion, $base_dn, $search_filter, $return_attributes, $pageSize = 100)
    {
        $base_dn = $this->prepareQueryString($base_dn, $protocolVersion);
        $search_filter = $this->prepareQueryString($search_filter, $protocolVersion);

        $ret = array();
        $pageCookie = "";
        do {
            ldap_control_paged_result($conn, $pageSize, true, $pageCookie);

            // Start search in LDAP directory.
            $sr = ldap_search($conn, $base_dn, $search_filter, $return_attributes, 0, 0, 0);
            if (!$sr) {
                break;
            }

            // Get the found entries as array.
            $entries = ldap_get_entries($conn, $sr);
            if (!$entries) {
                break;
            }

            $count = $entries["count"];
            for ($i = 0; $i < $count; ++$i) {
                // A $entry (array) contains all attributes of a single dataset from LDAP.
                $entry = $entries[$i];

                // Create a new object which will hold the attributes.
                // And add the default attribute "dn".
                $o = $this->createObjectFromEntry($entry, $protocolVersion);
                $ret[] = $o;
            }

            ldap_control_paged_result_response($conn, $sr, $pageCookie);
        } while ($pageCookie !== null && $pageCookie != "");
        return $ret;
    }

    /**
     * [7.4.0 , 8.0.0+]
     *
     * @param \LDAP\Connection $conn
     * @param string $protocolVersion
     * @param string $base_dn
     * @param string $search_filter
     * @param string $return_attributes
     * @param integer $pageSize
     * @param string $oid
     * @return array of stdClass objects with property values defined by $return_attributes+"dn"
     */
    private function objectSearch_74_to_80($conn, $protocolVersion, $base_dn, $search_filter, $return_attributes, $pageSize = 100, $oid = '1.2.840.113556.1.4.319')
    {
        $base_dn = $this->prepareQueryString($base_dn, $protocolVersion);
        $search_filter = $this->prepareQueryString($search_filter, $protocolVersion);

        $ret = [];

        $cookie = '';
        do {
            $controls = [
                [
                    'oid' => $oid,
                    // 'iscritical' => false,
                    'value' => ['size' => $pageSize, 'cookie' => $cookie]
                ]
            ];

            // Start search in LDAP directory.
            $sr = ldap_search($conn, $base_dn, $search_filter, $return_attributes, 0, 0, 0, 0, $controls);
            if (!$sr) {
                break;
            }

            // Get the found entries as array.
            $entries = ldap_get_entries($conn, $sr);
            if (!$entries) {
                break;
            }

            $count = $entries["count"];
            for ($i = 0; $i < $count; ++$i) {
                // A $entry (array) contains all attributes of a single dataset from LDAP.
                $entry = $entries[$i];

                // Create a new object which will hold the attributes.
                // And add the default attribute "dn".
                $o = $this->createObjectFromEntry($entry, $protocolVersion);
                $ret[] = $o;
            }

            ldap_parse_result($conn, $sr, $resultCode, $matchedDN, $errorMessage, $referrals, $serverControls);
            if (isset($serverControls[$oid]['value']['cookie'])) {
                // You need to pass the cookie from the last call to the next one
                $cookie = $serverControls[$oid]['value']['cookie'];
                // $pageSize = $count;
            } else {
                $cookie = '';
            }
        } while (!empty($cookie));

        return $ret;
    }

    /**
     * Creates a stdClass object with a property for each attribute.
     * For example:
     *   Entry ( "sn" => "Chuck Norris", "kick" => "Round house kick" )
     * Will return the stdClass object with following properties:
     *   stdClass->sn
     *   stdClass->kick
     *
     * @return stdClass
     */
    private function createObjectFromEntry(&$entry, $protocolVersion)
    {
        // Create a new user object which will hold the attributes.
        // And add the default attribute "dn".
        $u = new stdClass();
        $u->dn = $this->prepareResultString($entry["dn"], $protocolVersion);

        // The number of attributes inside the $entry array.
        $att_count = $entry["count"];

        for ($j = 0; $j < $att_count; $j++) {
            $attr_name = $entry[$j];
            $attr_value = $entry[$attr_name];
            $attr_value_count = $entry[$attr_name]["count"];

            // Use single scalar object for the attr value.
            if ($attr_value_count == 1) {
                $attr_single_value = $this->prepareResultString($attr_value[0], $protocolVersion);
                $u->$attr_name = $attr_single_value;
            } else {
                $attr_multi_value = array();
                for ($n = 0; $n < $attr_value_count; $n++) {
                    $attr_multi_value[] = $this->prepareResultString($attr_value[$n], $protocolVersion);
                }
                $u->$attr_name = $attr_multi_value;
            }
        }
        return $u;
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

        if (substr($dataSource['ldap_host'], 0, strlen('ldap://')) != 'ldap://' && substr($dataSource['ldap_host'], 0, strlen('ldaps://')) != 'ldaps://') {
            return message(200, 0, 'ldap主机名必须以 ldap:// 或者 ldaps:// 开始');
        }

        if (preg_match('/\:[0-9]+/', $dataSource['ldap_host'], $matches)) {
            return message(200, 0, 'ldap主机名不可携带端口');
        }

        $connection = ldap_connect(rtrim(trim($dataSource['ldap_host']), '/') . ':' . $dataSource['ldap_port'] . '/');
        if (!$connection) {
            return message(200, 0, '连接失败');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        // todo
        // ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, 10);
        // ldap_set_option($connection, LDAP_OPT_REFERRALS, false);

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

            // The standard attributes.
            $attributes = explode(',', $dataSource['user_attributes']);

            // Include the attribute which is used in the "member" attribute of a group-entry.
            if (isset($dataSource['groups_to_user_attribute_value'])) {
                $attributes[] = $dataSource['groups_to_user_attribute_value'];
            }

            $ldapUsers = $this->objectSearch($connection, $dataSource['ldap_version'], $dataSource['user_base_dn'], $dataSource['user_search_filter'], $attributes);

            $ldapUsersLen = count($ldapUsers);

            $up_name = $attributes[0];
            $users = [];
            for ($i = 0; $i < $ldapUsersLen; ++$i) {
                if (!property_exists($ldapUsers[$i], $up_name)) {
                    continue;
                }
                $users[] = $ldapUsers[$i]->$up_name;
            }

            return message(200, 1, '成功', [
                'count' => $ldapUsersLen,
                'users' => implode(',', $users),
                'success' => count($users),
                'fail' => $ldapUsersLen - count($users)
            ]);
        } elseif ($type == 'group') {
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

            $attributes = explode(',', $dataSource['group_attributes']);

            $includeMembers = false;
            if ($includeMembers) {
                $attributes[] = $dataSource['groups_to_user_attribute'];
            }

            $ldapGroups = $this->objectSearch($connection, $dataSource['ldap_version'], $dataSource['group_base_dn'], $dataSource['group_search_filter'], $attributes);

            $ldapGroupsLen = count($ldapGroups);

            $group_name_property = $attributes[0];
            $groups = [];
            for ($i = 0; $i < $ldapGroupsLen; ++$i) {
                if (!property_exists($ldapGroups[$i], $group_name_property)) {
                    continue;
                }
                $groups[] = $ldapGroups[$i]->$group_name_property;
            }

            return message(200, 1, '成功', [
                'count' => $ldapGroupsLen,
                'groups' => implode(',', $groups),
                'success' => count($groups),
                'fail' => $ldapGroupsLen - count($groups)
            ]);
        }
    }

    /**
     * ldap用户登录
     *
     * @return void
     */
    public function LdapUserLogin($username, $password)
    {
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }
        $dataSource = $dataSource['ldap'];

        $connection = ldap_connect(rtrim(trim($dataSource['ldap_host']), '/') . ':' . $dataSource['ldap_port'] . '/');
        if (!$connection) {
            return false;
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return false;
        }

        $attributes = explode(',', $dataSource['user_attributes']);

        $result = ldap_search($connection, $dataSource['user_base_dn'], sprintf('%s=%s', $attributes[0], $username));

        $entry = ldap_first_entry($connection, $result);

        $attrs = ldap_get_attributes($connection, $entry);

        $user_dn = ldap_get_dn($connection, $entry);

        $result = @ldap_bind($connection, $user_dn, $password);
        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * 获取ldap用户列表
     *
     * @return object
     */
    public function GetLdapUsers()
    {
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }
        $dataSource = $dataSource['ldap'];

        $connection = ldap_connect(rtrim(trim($dataSource['ldap_host']), '/') . ':' . $dataSource['ldap_port'] . '/');
        if (!$connection) {
            return message(200, 0, '连接失败');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return message(200, 0, sprintf('连接失败: %s', ldap_error($connection)));
        }

        // The standard attributes.
        $attributes = explode(',', $dataSource['user_attributes']);

        // Include the attribute which is used in the "member" attribute of a group-entry.
        if (isset($dataSource['groups_to_user_attribute_value'])) {
            $attributes[] = $dataSource['groups_to_user_attribute_value'];
        }

        $ldapUsers = $this->objectSearch($connection, $dataSource['ldap_version'], $dataSource['user_base_dn'], $dataSource['user_search_filter'], $attributes);

        $ldapUsersLen = count($ldapUsers);

        $up_name = $attributes[0];
        $users = [];
        for ($i = 0; $i < $ldapUsersLen; ++$i) {
            if (!property_exists($ldapUsers[$i], $up_name)) {
                continue;
            }
            $users[] = $ldapUsers[$i]->$up_name;
        }

        return message(200, 1, '成功', [
            'object' => $ldapUsers,
            'users' => $users
        ]);
    }

    /**
     * 获取ldap分组列表
     *
     * @return array
     */
    public function GetLdapGroups($includeMembers = false)
    {
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }
        $dataSource = $dataSource['ldap'];

        $connection = ldap_connect(rtrim(trim($dataSource['ldap_host']), '/') . ':' . $dataSource['ldap_port'] . '/');
        if (!$connection) {
            return message(200, 0, '连接失败');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $dataSource['ldap_version']);

        $result = @ldap_bind($connection, $dataSource['ldap_bind_dn'], $dataSource['ldap_bind_password']);
        if (!$result) {
            return message(200, 0, sprintf('连接失败: %s', ldap_error($connection)));
        }

        $attributes = explode(',', $dataSource['group_attributes']);

        if ($includeMembers) {
            $attributes[] = $dataSource['groups_to_user_attribute'];
        }

        $ldapGroups = $this->objectSearch($connection, $dataSource['ldap_version'], $dataSource['group_base_dn'], $dataSource['group_search_filter'], $attributes);

        $ldapGroupsLen = count($ldapGroups);

        $group_name_property = $attributes[0];
        $groups = [];
        for ($i = 0; $i < $ldapGroupsLen; ++$i) {
            if (!property_exists($ldapGroups[$i], $group_name_property)) {
                continue;
            }
            $groups[] = $ldapGroups[$i]->$group_name_property;
        }

        return message(200, 1, '成功', [
            'object' => $ldapGroups,
            'groups' => $groups
        ]);
    }

    /**
     * 分组(ldap) => authz
     *
     * @return array
     */
    public function SyncLdapToAuthz()
    {
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }
        $dataSource = $dataSource['ldap'];

        $authzContent = $this->authzContent;

        //清空原有分组
        $authzContent = $this->SVNAdmin->ClearGroupSection($authzContent);
        if (is_numeric($authzContent)) {
            if ($authzContent == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else {
                return message(200, 0, "错误码$authzContent");
            }
        }

        //从ldap获取分组和用户
        $users = $this->GetLdapUsers();
        if ($users['status'] != 1) {
            return message($users['code'], $users['status'], $users['message'], $users['data']);
        }
        $users = $users['data']['object'];

        $groups = $this->GetLdapGroups(true);
        if ($groups['status'] != 1) {
            return message($groups['code'], $groups['status'], $groups['message'], $groups['data']);
        }
        $groups = $groups['data']['object'];

        //一个分组条目中表示分组名的属性
        $groups_attributes = explode(',', $dataSource['group_attributes']);
        $gp_name = strtolower($groups_attributes[0]);

        //分组下包含多个对象 具备此属性的对象才可被识别为分组的成员 如 member
        $gp_member_id = strtolower($dataSource['groups_to_user_attribute']);

        //一个用户条目中表示用户名的属性
        $users_attributes = explode(',', $dataSource['user_attributes']);
        $up_name = strtolower($users_attributes[0]);

        //表示分组下的成员的唯一标识的属性 如 distinguishedName
        $up_id = strtolower($dataSource['groups_to_user_attribute_value']);

        foreach ($groups as $g) {
            if (!property_exists($g, $gp_name)) {
                //搜索的对象不存在 group-name
                continue;
            }

            //检查分组名是否合法
            $checkResult = $this->checkService->CheckRepGroup($g->$gp_name);
            if ($checkResult['status'] != 1) {
                continue;
            }

            //添加分组
            $result = $this->SVNAdmin->AddGroup($authzContent, $g->$gp_name);
            if (is_numeric($result)) {
                if ($result == 612) {
                    return message(200, 0, '文件格式错误(不存在[groups]标识)');
                } elseif ($result == 820) {
                    //分组已存在
                    continue;
                } else {
                    return message(200, 0, "错误码$result");
                }
            }
            $authzContent = $result;

            if (!property_exists($g, $gp_member_id)) {
                //分组下无成员
            } elseif (is_array($g->$gp_member_id)) {
                //分组下多个成员
                foreach ($g->$gp_member_id as $member_id) {
                    //获取成员用户名
                    foreach ($users as $u) {
                        if (!property_exists($u, $up_id)) {
                            continue;
                        }
                        if ($u->$up_id == $member_id) {
                            //为分组添加成员
                            $result = $this->SVNAdmin->UpdGroupMember($authzContent, $g->$gp_name, $u->$up_name, 'user', 'add');
                            if (is_numeric($result)) {
                                if ($result == 612) {
                                    return message(200, 0, '文件格式错误(不存在[groups]标识)');
                                } elseif ($result == 803) {
                                    $result = $authzContent;
                                } else {
                                    return message(200, 0, "错误码$result");
                                }
                            }
                            $authzContent = $result;
                            break;
                        }
                    }
                }
            } elseif (is_string($g->$gp_member_id)) {
                //分组下单个成员
                $member_id = $g->$gp_member_id;
                //获取成员用户名
                foreach ($users as $u) {
                    if ($u->$up_id == $member_id) {
                        //为分组添加成员
                        $result = $this->SVNAdmin->UpdGroupMember($authzContent, $g->$gp_name, $u->$up_name, 'user', 'add');
                        if (is_numeric($result)) {
                            if ($result == 612) {
                                return message(200, 0, '文件格式错误(不存在[groups]标识)');
                            } elseif ($result == 803) {
                                $result = $authzContent;
                            } else {
                                return message(200, 0, "错误码$result");
                            }
                        }
                        $authzContent = $result;
                        break;
                    }
                }
            }
        }

        file_put_contents($this->configSvn['svn_authz_file'], $authzContent);

        parent::RereadAuthz();

        return message();
    }
}
