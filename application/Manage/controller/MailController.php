<?php
namespace app\Manage\controller;

use app\Manage\model\MailModel;
use app\Manage\validate\MailValidate;
use think\Controller;
use think\Session;
use think\Config;

class MailController extends BaseController
{
    public function index()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new MailValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new MailModel();
                if ($model->allowField(true)->save($post, ['id' => $post['id']])) {
                    echo json_encode(['code' => 1, 'msg' => '保存成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '保存失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            // 网站参数
            $mail = MailModel::get(['id' => 1])->toArray();
            $this->assign('info', $mail);

            return view();
        }
    }

    public function send()
    {
        if ($this->request->isPost()) {
            $config = MailModel::get(1);
            $toemail = $config['acceptEmail'];
            $name = $config['senderName'];
            $subject = '邮件发送测试';
            $content = '恭喜你，邮件测试成功。';
            if (MailModel::send_mail($toemail,$name,$subject,$content)) {
                echo json_encode(['code' => 1, 'msg' => '发送成功']);
                exit;
            } else {
                echo json_encode(['code' => 0, 'msg' => '发送失败']);
                exit;
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit; 
        }
    }
}
