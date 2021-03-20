<?php

/*
 * 控制器基类，所有的控制器都要继承此类
 */

//require model
require_once BASE_PATH . '/app/model/connModel.class.php';

//require controller
require_once BASE_PATH . '/app/controller/client.class.php';
require_once BASE_PATH . '/app/controller/config.class.php';
require_once BASE_PATH . '/app/controller/crontab.class.php';
require_once BASE_PATH . '/app/controller/firewall.class.php';
require_once BASE_PATH . '/app/controller/mail.class.php';
require_once BASE_PATH . '/app/controller/svnserve.class.php';
require_once BASE_PATH . '/app/controller/system.class.php';
require_once BASE_PATH . '/app/controller/user.class.php';

//require function
require_once BASE_PATH . '/app/function/file.function.php';
require_once BASE_PATH . '/app/function/web.function.php';

class Controller {

    public $database_medoo;
    public $this_userid;
    public $this_username;

    function __construct() {
        $this->database_medoo = (new connModel())->GetConn();
        $this->prehandler();
        $this->this_userid = $this->GetUserInfoByToken(MY_TOKEN)["userid"];
        $this->this_username = $this->GetUserInfoByToken(MY_TOKEN)["username"];
    }

    //预操作,检查Token
    final function prehandler() {
        if (MY_FUNCTION != 'Login') {
            $data = $this->CheckToken(MY_TOKEN);
            if ($data['code'] != '200') {
                $result = array(
                    'status' => '0',
                    'code' => $data['code'],
                    'message' => $data['message']
                );
                return $result;
            }
        }
    }

    //生成token
    final function CreateToken($userid) {
        $time = time();
        $end_time = time() + 86400;
        $info = $userid . '.' . $time . '.' . $end_time; //设置token过期时间为一天
        //根据以上信息信息生成签名（密钥为 siasqr)
        $signature = hash_hmac('md5', $info, SIGNATURE);
        //最后将这两部分拼接起来，得到最终的Token字符串
        return $token = $info . '.' . $signature;
    }

    //检查token
    final function CheckToken($token) {
        if (!isset($token) || empty($token)) {
            $data['code'] = '400';
            $data['message'] = '非法请求';
            return $data;
        }
        //对比token
        $explode = explode('.', $token); //以.分割token为数组
        if (!empty($explode[0]) && !empty($explode[1]) && !empty($explode[2]) && !empty($explode[3])) {
            $info = $explode[0] . '.' . $explode[1] . '.' . $explode[2]; //信息部分
            $true_signature = hash_hmac('md5', $info, SIGNATURE); //正确的签名
            if (time() > $explode[2]) {
                $data['code'] = '401';
                $data['message'] = 'Token已过期,请重新登录';
                return $data;
            }
            if ($true_signature == $explode[3]) {
                $data['code'] = '200';
                $data['message'] = 'Token合法';
                return $data;
            } else {
                $data['code'] = '400';
                $data['message'] = 'Token不合法';
                return $data;
            }
        } else {
            $data['code'] = '400';
            $data['message'] = 'Token不合法';
            return $data;
        }
    }

    //根据token获取userid
    final function GetUserInfoByToken($token) {
        $explode = explode('.', $token);
        $result = $this->database_medoo->select("user", ["username"], ["id" => $explode[0]]);
        $data = array(
            "userid" => $explode[0],
            "username" => $result[0]["username"]
        );
        return $data;
    }

    //请求与应答模式
    final function RequestReplyExec($shell) {
        //创建套接字上下文
        $context = new ZMQContext();
        //创建ZMQ请求套接字
        $req = new ZMQSocket($context, ZMQ::SOCKET_REQ);
        //连接到端口
        $req->connect("tcp://127.0.0.1:6666");
        //对请求字符串进行编码 防止传输过程中字符串信息丢失
        $shell = urlencode($shell);
        //发送请求
        $req->send($shell);
        //接收回应
        $reply = $req->recv();
        return $reply;
    }

}
