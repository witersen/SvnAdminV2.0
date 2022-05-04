<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 18:54:21
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail extends Core
{
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
    private function Send($host, $auth, $user, $pass, $encryption, $autotls, $port, $subject, $body, $to = [], $cc = [], $bcc = [], $reply = ['address' => '', 'name' => ''], $from = ['address' => '', 'name' => ''], $timeout = 10)
    {
        try {
            //不允许输出 debug 信息
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF;

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
            } else if ($encryption == 'SSL') {
                //加密方式为SSL
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                //是否配置自动启用TLS
                $this->mail->SMTPAutoTLS = $autotls;
            } else if ($encryption == 'TLS') {
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
    public function GetEmail(Request $request)
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
            'from' => '',

            //启用状态
            'status' => false
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
    public function EditEmail(Request $request)
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
            ])
        ], [
            'option_name' => 'mail_smtp'
        ]);
        return message();
    }

    /**
     * 发送测试邮件
     */
    public function SendTest(Request $request)
    {
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
            ['address' => $this->payload['to'], 'name' => '']
        ];
        $cc = [];
        $bcc = [];
        $reply = [];
        $from = ['address' => $this->payload['from'], 'name' => ''];
        $timeout = 10;
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
}
