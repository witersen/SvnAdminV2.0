<?php

declare(strict_types=1);

/*
 * 与邮件操作相关
 */

require BASE_PATH . '/extension/PHPMailer/src/Exception.php';
require BASE_PATH . '/extension/PHPMailer/src/PHPMailer.php';
require BASE_PATH . '/extension/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail extends Controller
{
    private $Mail;
    private $Config;

    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        // 实例化并传递 true 将启用异常
        $this->Mail = new PHPMailer(true);
        $this->Mail->setLanguage('zh_cn', BASE_PATH . '/extension/PHPMailer/language/'); //加载错误消息翻译包

        $this->Config = new Config();
    }

    //用户操作触发邮件发送机制
    function SendMail($send_title, $send_content)
    {
        //系统未开启邮件服务
        if (ALL_MAIL_STATUS == "0") {
            return;
        }
        //未添加邮件服务
        $result = mail_host;
        if (empty($result)) {
            return;
        }
        //添加的邮件服务未启用
        if (single_mail_status != "1") {
            return;
        }
        //邮件服务器选项
        $port = mail_port;
        $host = mail_host;
        $username = mail_user;
        $password = mail_password;
        $from = send_mail;
        $to = MANAGE_EMAIL;

        //设置选项
        try {
            //服务器配置 
            $this->Mail->SMTPDebug = SMTP::DEBUG_OFF; // 关闭debug模式
            $this->Mail->isSMTP(); // 使用 SMTP
            $this->Mail->Host = $host; // SMTP服务器
            $this->Mail->SMTPAuth = true; // 允许 SMTP 认证 
            $this->Mail->Username = $username; // SMTP 用户名
            $this->Mail->Password = $password; // SMTP 密码
            $this->Mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 允许tls协议 
            $this->Mail->Port = $port; // TCP 端口
            //接收设置
            $this->Mail->setFrom($from, '发送方：SvnAdmin'); //发件人
            $this->Mail->addAddress($to); //收件人 名字可选 可添加多个收件人 
            $this->Mail->addReplyTo($from, '发送方：SvnAdmin'); //回复的时候回复给哪个邮箱 名字可选 建议和发件人一致 
            //            $this->Mail->addCC('cc@example.com'); //抄送
            //            $this->Mail->addBCC('bcc@example.com'); //密送
            //发送附件
            //            $this->Mail->addAttachment('/var/tmp/file.tar.gz'); // Add attachments
            //            $this->Mail->addAttachment('/tmp/image.jpg', 'new.jpg'); // Optional name
            //内容设置
            $this->Mail->isHTML(false); // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容 
            $this->Mail->Subject = $send_title;
            $this->Mail->Body = $send_content;
            //            $this->Mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';
            $this->Mail->send();
        } catch (Exception $e) {
            //            echo "邮件发送失败: {$mail->ErrorInfo}";
        }
    }

    //发送测试邮件
    function SendTestMail($requestPayload)
    {
        $host = trim($requestPayload['host']);
        $port = trim($requestPayload['port']);
        $username = trim($requestPayload['username']);
        $password = trim($requestPayload['password']);
        $from = trim($requestPayload['from']);
        $to = trim($requestPayload['to']);

        if (empty($host) || empty($port) || empty($username) || empty($password) || empty($from) || empty($to)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        //设置选项
        try {
            //服务器配置 
            $this->Mail->SMTPDebug = SMTP::DEBUG_OFF; // 关闭debug模式
            $this->Mail->isSMTP(); // 使用 SMTP
            $this->Mail->Host = $host; // SMTP服务器
            $this->Mail->SMTPAuth = true; // 允许 SMTP 认证 
            $this->Mail->Username = $username; // SMTP 用户名
            $this->Mail->Password = $password; // SMTP 密码
            $this->Mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 允许tls协议 
            $this->Mail->Port = $port; // TCP 端口
            //接收设置
            $this->Mail->setFrom($from, '发送方：SvnAdmin'); //发件人
            $this->Mail->addAddress($to); //收件人 名字可选 可添加多个收件人 
            $this->Mail->addReplyTo($from, '发送方：SvnAdmin'); //回复的时候回复给哪个邮箱 名字可选 建议和发件人一致 
            //            $this->Mail->addCC('cc@example.com'); //抄送
            //            $this->Mail->addBCC('bcc@example.com'); //密送
            //发送附件
            //            $this->Mail->addAttachment('/var/tmp/file.tar.gz'); // Add attachments
            //            $this->Mail->addAttachment('/tmp/image.jpg', 'new.jpg'); // Optional name
            //内容设置
            $this->Mail->isHTML(false); // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容 
            $this->Mail->Subject = 'Svn Admin系统测试邮件';
            $this->Mail->Body = '尊敬的用户，您好！此邮件为Svn Admin系统的测试邮件，当您收到此邮件，代表您的邮件服务器已经配置正确。';
            //            $this->Mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';
            $this->Mail->send();
            $data['status'] = 1;
            $data['message'] = '成功';
            return $data;
        } catch (Exception $e) {
            $data['status'] = 0;
            $data['message'] = '发送失败 ' . $this->Mail->ErrorInfo;
            return $data;
        }
    }

    //保存邮件设置
    function SetMailInfo($requestPayload)
    {
        $this->Config->Update('mail_host', trim($requestPayload['host']));
        $this->Config->Update('mail_port', trim($requestPayload['port']));
        $this->Config->Update('mail_user', trim($requestPayload['username']));
        $this->Config->Update('mail_password', trim($requestPayload['password']));
        $this->Config->Update('send_mail', trim($requestPayload['from']));

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //获取邮件配置
    function GetMailInfo($requestPayload)
    {
        $data = array(
            "protocol_type" => mail_protocol_type,
            "smtp_host" => mail_host,
            "smtp_port" => (int) mail_port,
            "mail_ssl_port" => (int) mail_ssl_port,
            "smtp_user" => mail_user,
            "smtp_password" => mail_password,
            "smtp_send_email" => send_mail,
            "single_mail_status" => (int) single_mail_status,
            "add_time" => "",
            "ps" => ""
        );
        $data['status'] = 1;
        $data['data'] = $data;
        $data['message'] = '成功';
        return $data;
    }
}
