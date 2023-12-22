<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class MailModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'mail';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

    protected function setCreatedAtAttr()
    {
        return time();
    }

    protected function setUpdatedAtAttr()
    {
        return time();
    }

    static public function send_mail($tomail, $name, $subject = '', $body = '', $attachment = null)
    {
        $config = self::mail_config();
        $mail = new \PHPMailer\PHPMailer();                             //实例化PHPMailer对象
        $mail->CharSet = 'UTF-8';                                       //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->IsSMTP();                                                // 设定使用SMTP服务
        $mail->SMTPDebug = 0;                                           // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
        $mail->SMTPAuth = true;                                         // 启用 SMTP 验证功能
        $mail->SMTPSecure = 'ssl';                                      // 使用安全协议
        $mail->Host = $config['smtpAddress'];                           // SMTP 服务器
        $mail->Port = 465;                                              // SMTP服务器的端口号
        $mail->Username = $config['emailAccount'];                      // SMTP服务器用户名
        $mail->Password = $config['emailPassword'];                     // SMTP服务器密码
        $mail->SetFrom($config['senderEmail'], $config['senderName']);
        $replyEmail = '';                                               //留空则为发件人EMAIL
        $replyName = '';                                                //回复名称（留空则为发件人名称）
        $mail->AddReplyTo($replyEmail, $replyName);
        $mail->Subject = $subject;
        $mail->MsgHTML($body);
        $mail->AddAddress($tomail, $name);
        if (is_array($attachment)) { // 添加附件
            foreach ($attachment as $file) {
                is_file($file) && $mail->AddAttachment($file);
            }
        }
        return $mail->Send() ? true : $mail->ErrorInfo;
    }

    protected function mail_config()
    {
        return self::get(1);
    }
}
