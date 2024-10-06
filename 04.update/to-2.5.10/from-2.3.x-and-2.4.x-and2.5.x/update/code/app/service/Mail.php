<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail extends Base
{
    private $mail;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->mail = new PHPMailer(true);
        $this->mail->setLanguage('zh_cn', BASE_PATH . '/extension/PHPMailer-6.6.0/language/'); //加载错误消息翻译包
    }

    /**
     * 发送邮件的模板函数
     *
     * @param string $host
     * @param bool $auth
     * @param string $user
     * @param string $pass
     * @param string $encryption ['' | 'none' | 'SSL' | 'TLS']
     * @param bool $autotls
     * @param int $port
     * @param string $subject
     * @param string $body
     * @param array $to
     * @param array $cc
     * @param array $bcc
     * @param array $reply
     * @param array $from
     * @param string $fromName
     * @param integer $timeout
     * @return void
     */
    private function Send($host, $auth, $user, $pass, $encryption, $autotls, $port, $subject, $body, $to = [], $cc = [], $bcc = [], $reply = ['address' => '', 'name' => ''], $from = ['address' => '', 'name' => ''], $timeout = 5)
    {
        try {
            //不允许输出 debug 信息
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
            // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;

            //使用 SMTP
            $this->mail->isSMTP();

            //配置 SMTP 主机        smtp.example.com
            $this->mail->Host = $host;

            if ($auth) {
                //允许 SMTP 认证
                $this->mail->SMTPAuth = $auth;

                //SMTP 用户名 user@example.com
                $this->mail->Username = $user;

                //SMTP 密码
                $this->mail->Password = $pass;
            }

            if ($encryption == 'none' || $encryption == '') {
                //不加密
                $this->mail->SMTPSecure = "";
                //是否配置自动启用TLS
                $this->mail->SMTPAutoTLS = $autotls;
            } elseif ($encryption == 'SSL') {
                //加密方式为SSL
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                //是否配置自动启用TLS
                $this->mail->SMTPAutoTLS = $autotls;
            } elseif ($encryption == 'TLS') {
                //加密方式为TLS
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            //端口
            $this->mail->Port = $port;

            //设置发送超时时间
            $this->mail->Timeout = $timeout;

            //收件人
            foreach ($to as $value) {
                $this->mail->addAddress($value['address'], $value['name']);
            }

            //抄送
            foreach ($cc as $value) {
                $this->mail->addCC($value['address'], $value['name']);
            }

            //密送
            foreach ($bcc as $value) {
                $this->mail->addBCC($value['address'], $value['name']);
            }

            //回复
            if ($reply != [] && $reply['address'] != '') {
                $this->mail->addReplyTo($reply['address'], $reply['name']);
            }

            //发件人
            if ($from['address'] != '') {
                $this->mail->setFrom($from['address'], $from['name']);
            }

            //是否以HTML文档格式发送  发送后客户端可直接显示对应HTML解析后的内容
            $this->mail->isHTML(false);

            //邮件主题
            $this->mail->Subject = $subject;

            //邮件内容
            $this->mail->Body = $body;

            //发送
            $this->mail->send();

            return true;
        } catch (Exception $e) {
            return $this->mail->ErrorInfo;
        }
    }

    /**
     * 获取邮件配置信息
     */
    public function GetMailInfo()
    {
        $mail_smtp = $this->database->get('options', [
            'option_value'
        ], [
            'option_name' => 'mail_smtp'
        ]);

        $mail_smtp_null = [
            //SMTP主机
            'host' => '',

            //加密方式 对于大多数服务器，建议使用TLS。 如果您的SMTP提供商同时提供SSL和TLS选项，我们建议您使用TLS。 
            'encryption' => 'none',

            //SMTP端口
            'port' => 25,

            //自动TLS 默认情况下，如果服务器支持TLS加密，则会自动使用TLS加密（推荐）。在某些情况下，由于服务器配置错误可能会导致问题，则需要将其禁用。
            'autotls' => true,

            //认证
            'auth' => true,

            //SMTP用户名 可以不为邮箱格式 如smtp.qq.com可以用QQ号码
            'user' => '',

            //SMTP密码
            'pass' => '',

            //发件人 一般与SMTP用户名相同 需要为邮箱格式
            'from' => ['address' => '', 'name' => ''],

            //启用状态
            'status' => false,

            //收件人邮箱
            'to' => [],

            //发送超时时间
            'timeout' => 5
        ];

        if ($mail_smtp == null) {
            $this->database->insert('options', [
                'option_name' => 'mail_smtp',
                'option_value' => serialize($mail_smtp_null),
                'option_description' => ''
            ]);
            return message(200, 1, '成功', $mail_smtp_null);
        }
        if ($mail_smtp['option_value'] == '') {
            $this->database->update('options', [
                'option_value' => serialize($mail_smtp_null),
            ], [
                'option_name' => 'mail_smtp',
            ]);
            return message(200, 1, '成功', $mail_smtp_null);
        }

        return message(200, 1, '成功', unserialize($mail_smtp['option_value']));
    }

    /**
     * 修改邮件配置信息
     */
    public function UpdMailInfo()
    {
        $this->database->update('options', [
            'option_value' => serialize([
                'host' => $this->payload['host'],
                'encryption' => $this->payload['encryption'],
                'port' => $this->payload['port'],
                'autotls' => $this->payload['autotls'],
                'auth' => $this->payload['auth'],
                'user' => $this->payload['user'],
                'pass' => $this->payload['pass'],
                'from' => $this->payload['from'],
                'status' => $this->payload['status'],
                'to' => $this->payload['to'],
                'timeout' => $this->payload['timeout']
            ])
        ], [
            'option_name' => 'mail_smtp'
        ]);
        return message();
    }

    /**
     * 发送测试邮件
     */
    public function SendMailTest()
    {
        $checkResult = funCheckForm($this->payload, [
            'host' => ['type' => 'string', 'notNull' => true],
            'auth' => ['type' => 'boolean'],
            'user' => ['type' => 'string', 'notNull' => true],
            'pass' => ['type' => 'string', 'notNull' => true],
            'encryption' => ['type' => 'string', 'notNull' => true],
            'autotls' => ['type' => 'boolean'],
            'port' => ['type' => 'integer', 'notNull' => true],
            'from' => ['type' => 'array'],
            'test' => ['type' => 'string', 'notNull' => true],
            'timeout' => ['type' => 'integer', 'notNull' => true],
            'user' => ['type' => 'string', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $host = $this->payload['host'];
        $auth = $this->payload['auth'];
        $user = $this->payload['user'];
        $pass = $this->payload['pass'];
        $encryption = $this->payload['encryption'];
        $autotls = $this->payload['autotls'];
        $port = $this->payload['port'];
        $subject = "SVNAdmin的测试邮件";
        $body = "此邮件为SVNAdmin系统发送的测试邮件，当您收到此邮件，代表您的邮件服务已经配置正确。";
        $to = [
            ['address' => $this->payload['test'], 'name' => '']
        ];
        $cc = [];
        $bcc = [];
        $reply = [];
        $from =  $this->payload['from'];
        $timeout = $this->payload['timeout'];
        $result = $this->Send(
            $host,
            $auth,
            $user,
            $pass,
            $encryption,
            $autotls,
            $port,
            $subject,
            $body,
            $to,
            $cc,
            $bcc,
            $reply,
            $from,
            $timeout
        );

        return message(200, $result === true ? 1 : 0, $result === true ? '发送成功' : $result);
    }

    /**
     * 发送通知邮件
     */
    public function SendMail($trigger, $subject, $body)
    {
        $mail_smtp = $this->GetMailInfo();
        $mail_smtp = $mail_smtp['data'];

        //检查邮件服务是否启用
        if (!$mail_smtp['status']) {
            return message(200, 0, '邮件服务未开启');
        }

        //检查触发条件
        $message_push = $this->GetPushInfo();
        $message_push = $message_push['data'];

        $triggers = array_column($message_push, 'trigger');
        if (!in_array($trigger, $triggers)) {
            return message(200, 0, '触发条件不存在');
        }
        $options = array_combine($triggers, array_column($message_push, 'enable'));
        if (!$options[$trigger]) {
            return message(200, 0, '触发条件未开启');
        }

        $host = $mail_smtp['host'];
        $auth = $mail_smtp['auth'];
        $user = $mail_smtp['user'];
        $pass = $mail_smtp['pass'];
        $encryption = $mail_smtp['encryption'];
        $autotls = $mail_smtp['autotls'];
        $port = $mail_smtp['port'];
        $to = $mail_smtp['to'];
        $cc = [];
        $bcc = [];
        $reply = [];
        $from = $mail_smtp['from'];
        $timeout = $mail_smtp['timeout'];
        $result = $this->Send(
            $host,
            $auth,
            $user,
            $pass,
            $encryption,
            $autotls,
            $port,
            $subject,
            $body,
            $to,
            $cc,
            $bcc,
            $reply,
            $from,
            $timeout
        );

        return message(200, $result === true ? 1 : 0, $result === true ? '发送成功' : $result);
    }

    /**
     * 任务计划触发通知邮件
     */
    public function SendMail2($subject, $body)
    {
        $mail_smtp = $this->GetMailInfo();
        $mail_smtp = $mail_smtp['data'];

        //检查邮件服务是否启用
        if (!$mail_smtp['status']) {
            return message(200, 0, '邮件服务未开启');
        }

        $host = $mail_smtp['host'];
        $auth = $mail_smtp['auth'];
        $user = $mail_smtp['user'];
        $pass = $mail_smtp['pass'];
        $encryption = $mail_smtp['encryption'];
        $autotls = $mail_smtp['autotls'];
        $port = $mail_smtp['port'];
        $to = $mail_smtp['to'];
        $cc = [];
        $bcc = [];
        $reply = [];
        $from = $mail_smtp['from'];
        $timeout = $mail_smtp['timeout'];
        $result = $this->Send(
            $host,
            $auth,
            $user,
            $pass,
            $encryption,
            $autotls,
            $port,
            $subject,
            $body,
            $to,
            $cc,
            $bcc,
            $reply,
            $from,
            $timeout
        );

        return message(200, $result === true ? 1 : 0, $result === true ? '发送成功' : $result);
    }

    /**
     * 获取消息推送信息配置
     */
    public function GetPushInfo()
    {
        $message_push = $this->database->get('options', [
            'option_value'
        ], [
            'option_name' => 'message_push'
        ]);

        $message_push_null = [
            [
                'trigger' => 'Common/Login',
                'type' => 'mail',
                'note' => '用户登录',
                'enable' => false,
            ],
            [
                'trigger' => 'Personal/EditAdminUserName',
                'type' => 'mail',
                'note' => '管理人员修改账户名',
                'enable' => false,
            ],
            [
                'trigger' => 'Personal/EditAdminUserPass',
                'type' => 'mail',
                'note' => '管理人员修改密码',
                'enable' => false,
            ],
            [
                'trigger' => 'Personal/EditSvnUserPass',
                'type' => 'mail',
                'note' => 'SVN用户修改密码',
                'enable' => false,
            ],
        ];

        if ($message_push == null) {
            $this->database->insert('options', [
                'option_name' => 'message_push',
                'option_value' => serialize($message_push_null),
                'option_description' => ''
            ]);

            return message(200, 1, '成功', $message_push_null);
        }
        if ($message_push['option_value'] == '') {
            $this->database->update('options', [
                'option_value' => serialize($message_push_null),
            ], [
                'option_name' => 'message_push',
            ]);

            return message(200, 1, '成功', $message_push_null);
        }

        return message(200, 1, '成功', unserialize($message_push['option_value']));
    }

    /**
     * 修改推送选项
     */
    function UpdPushInfo()
    {
        $this->database->update('options', [
            'option_value' => serialize($this->payload['listPush'])
        ], [
            'option_name' => 'message_push'
        ]);

        return message();
    }
}
