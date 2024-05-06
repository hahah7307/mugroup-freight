<?php
namespace app\Manage\controller;

use app\Manage\model\AccountModel;
use think\exception\DbException;

class AuthController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index($controller)
    {
        $controllers = explode(',', $controller);
        $whereIn = '"' . implode('","', $controllers) . '"';

        $userModel = new AccountModel();
        $sql = '
SELECT
	b.id node_id,
	a.NAME controller,
	b.NAME action,
	f.nickname 
FROM
	mu_admin_node a
	LEFT JOIN mu_admin_node b ON a.id = b.parent_id
	LEFT JOIN mu_admin_access c ON b.id = c.node_id
	LEFT JOIN mu_admin_role d ON c.role_id = d.id
	LEFT JOIN mu_admin_user_role e ON d.id = e.role_id
	LEFT JOIN mu_admin_user f ON e.user_id = f.id 
WHERE
    f.status = 1 AND
	a.CODE IN ( ' . $whereIn . ' );
        ';
        $res = $userModel->query($sql);
        $list = [];
        $count = [];
        foreach ($res as $v) {
            $list[$v['nickname']][$v['controller']][] = $v['action'];
            $count[$v['nickname']]['num'] ++;
            $count[$v['nickname']][$v['controller']] ++;
        }
        $this->assign('list', $list);
        $this->assign('count', $count);

        return view();
    }
}
