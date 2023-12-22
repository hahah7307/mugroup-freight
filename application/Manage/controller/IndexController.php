<?php
namespace app\Manage\controller;

use app\Manage\model\InquiryModel;
use app\Manage\model\ProductModel;
use app\Manage\model\PriceModel;
use app\Manage\model\MemberModel;
use app\Manage\model\NoticeModel;
use app\Manage\model\PageModel;
use think\Controller;
use think\Session;
use think\Config;

class IndexController extends BaseController
{
    public function index()
    {

        return view();
    }

    public function initMenu()
    {
        if ($this->request->isPost()) {
            $info = $this->request->post('info');
            if ($info) {
                Session::set(Config::get('USER_MENU'), str_replace("\n", '', $info));

                echo json_encode(['code' => 1, 'msg' => "操作成功"]);
                exit;
            } else {
                echo json_encode(['code' => 0, 'msg' => "操作失败"]);
                exit;
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => "异常操作"]);
            exit;
        }
    }
}
