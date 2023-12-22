<?php
namespace app\Manage\controller;

use app\Manage\model\WebsiteModel;
use app\Manage\model\AccountModel;
use app\Manage\validate\WebsiteValidate;
use app\Manage\validate\AccountValidate;
use think\Controller;
use think\Session;
use think\Config;

class SiteController extends BaseController
{
    public function web_params()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['language_id'] = $this->language_id;
            $post['status'] = 1;
            if (!empty($post['logo'])) {
                $post['logo'] = implode($post['logo'], ',');
            }
            if (!empty($post['qrcode'])) {
                $post['qrcode'] = implode($post['qrcode'], ',');
            }
            $dataValidate = new WebsiteValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new WebsiteModel();
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
            $website = WebsiteModel::get(['language_id' => $this->language_id, 'status' => WebsiteModel::STATUS_ACTIVE])->toArray();
            $this->assign('info', $website);

            return view();
        }
    }

    public function admin()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new AccountValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new AccountModel();
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
            // 管理员
            $admin = AccountModel::get(['id' => Session::get(Config::get('USER_LOGIN_FLAG')), 'status' => AccountModel::STATUS_ACTIVE])->toArray();
            $this->assign('info', $admin);

            return view();
        }
    }

    public function repass()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $account = AccountModel::get(['id' => Session::get(Config::get('USER_LOGIN_FLAG')), 'status' => AccountModel::STATUS_ACTIVE])->toArray();
            if ($post['password'] != $post['repassword']) {
                echo json_encode(['code' => 0, 'msg' => '重复密码错误']);
                exit;
            }
            if (encPass($post['password'], $account['password_hash']) == $account['password']) {
                echo json_encode(['code' => 0, 'msg' => '与原密码相同']);
                exit;
            }
            $post['password'] = encPass($post['password'], $account['password_hash']);
            $dataValidate = new AccountValidate();
            if ($dataValidate->scene('password')->check($post)) {
                $model = new AccountModel();
                if ($model->allowField(true)->save($post, ['id' => $post['id']])) {
                    Session::delete(Config::get('USER_LOGIN_FLAG'));
                    Session::delete(Config::get('USER_LOGIN_TIME'));

                    echo json_encode(['code' => 1, 'msg' => '保存成功，请重新登录']);
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
            // 管理员
            $admin = AccountModel::get(['id' => Session::get(Config::get('USER_LOGIN_FLAG')), 'status' => AccountModel::STATUS_ACTIVE])->toArray();
            $this->assign('info', $admin);

            return view();
        }
    }
}
