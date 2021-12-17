<?php

/*
 * 与防火墙操作相关
 */

class Firewall extends Controller {

    function __construct() {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    //高级设置 使svnserve服务的端口通过防火墙
    function SetFirewallStatus($requestPayload) {
        $action = $requestPayload['action'];

        if (empty($action)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        switch ($action) {
            case 'startFirewall':
                RequestReplyExec('systemctl start firewalld');
                RequestReplyExec('firewall-cmd --zone=public --add-port=80/tcp --permanent'); //启动的同时将80加入 使得web服务正常运行
                RequestReplyExec('firewall-cmd --zone=public --add-port=3690/tcp --permanent'); //启动的同时将80加入 使得web服务正常运行
                RequestReplyExec('firewall-cmd --reload');
                break;
            case 'restartFirewall':
                RequestReplyExec('systemctl restart firewalld');
                RequestReplyExec('firewall-cmd --zone=public --add-port=80/tcp --permanent'); //启动的同时将80加入 使得web服务正常运行
                RequestReplyExec('firewall-cmd --zone=public --add-port=3690/tcp --permanent'); //启动的同时将80加入 使得web服务正常运行
                RequestReplyExec('firewall-cmd --reload');
                break;
            case 'stopFirewall':
                RequestReplyExec('systemctl stop firewalld');
                break;
        }

        sleep(1);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //设置防火墙规则
    function SetFirewallPolicy($requestPayload) {
        $protocal = 'tcp';
        $port = $requestPayload['port'];
        $type = $requestPayload['type'] ? 'add' : 'remove';

        if (empty($protocal) || empty($port) || empty($type)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }
        RequestReplyExec('firewall-cmd --zone=public --' . $type . '-port=' . $port . '/' . $protocal . ' --permanent');
        RequestReplyExec('firewall-cmd --reload');

        sleep(1);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //获取防火墙规则
    function GetFirewallPolicy($requestPayload) {
        //获取80 443 3690是否加入防火墙
        $info = RequestReplyExec('ps auxf|grep -v "grep"|grep firewalld');
        if ($info == ISNULL) {
            $info = array();
            $info['svn'] = false;
            $info['http'] = false;
            $info['https'] = false;

            $data['status'] = 1;
            $data['message'] = '成功';
            $data['data'] = $info;
            return $data;
        }

        $info = array();
        $result = trim(RequestReplyExec('firewall-cmd --query-port=80/tcp'));
        if ($result == 'yes') {
            $info['http'] = true;
        } else {
            $info['http'] = false;
        }

        $result = trim(RequestReplyExec('firewall-cmd --query-port=443/tcp'));
        if ($result == 'yes') {
            $info['https'] = true;
        } else {
            $info['https'] = false;
        }

        $result = trim(RequestReplyExec('firewall-cmd --query-port=3690/tcp'));
        if ($result == 'yes') {
            $info['svn'] = true;
        } else {
            $info['svn'] = false;
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $info;
        return $data;
    }

    //获取防火墙状态
    function GetFirewallStatus($requestPayload) {
        $info = RequestReplyExec('ps auxf|grep -v "grep"|grep firewalld');
        if ($info == ISNULL) {
            $info = array();
            $info['status'] = '已停止';
            $info['type'] = 'warning';

            $data['status'] = 1;
            $data['message'] = '成功';
            $data['data'] = $info;
            return $data;
        }
        $info = array();
        $info['status'] = '运行中';
        $info['type'] = 'success';

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $info;
        return $data;
    }

}
