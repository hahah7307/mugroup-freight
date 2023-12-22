<?php
namespace app\Home\controller;
use app\Home\model\AppTemplate;
use think\Controller;
use think\Config;
use think\Session;
use think\Cookie;

class BaseController extends Controller
{
    public function _initialize()
    {
		parent::_initialize();
    }
}
