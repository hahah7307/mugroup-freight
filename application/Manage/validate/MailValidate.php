<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class MailValidate extends Validate
{
    protected $rule = [
        'senderEmail'           =>  'require|email',
        'senderName'            =>  'require',
        'smtpAddress'           =>  'require',
        'smtpPort'              =>  'require|number',
        'emailAccount'          =>  'require|email',
        'emailPassword'         =>  'require',
        'acceptEmail'           =>  'require|email',
    ];

    protected $message = [

    ];

    protected $field = [
        'senderEmail'           =>  '发件人邮箱',
        'senderName'            =>  '发件人名称',
        'smtpAddress'           =>  'SMTP地址',
        'smtpPort'              =>  'SMTP端口',
        'emailAccount'          =>  '邮箱账号',
        'emailPassword'         =>  '邮箱密码',
        'acceptEmail'           =>  '接受邮箱地址',
    ];

    protected $scene = [
        'add'       =>  ['senderEmail', 'senderName', 'smtpAddress', 'smtpPort', 'emailAccount', 'emailPassword', 'acceptEmail'],
        'edit'      =>  ['senderEmail', 'senderName', 'smtpAddress', 'smtpPort', 'emailAccount', 'emailPassword', 'acceptEmail'],
    ];
}
