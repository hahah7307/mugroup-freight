<?php
namespace app\Home\controller;
use app\Home\model\StorageRuleModel;
use app\Home\model\DeliverFeeModel;
use app\Home\model\AHS;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Fill;
use think\Cookie;
use think\exception\DbException;

class IndexController extends BaseController
{
    public function index()
    {
        $this->redirect(url('Manage/index/index'));
    }
}
