<?php

namespace app\Manage\controller;

use think\Controller;
use app\Manage\model\ImageModel;
use app\Manage\model\DownloadModel;
use app\Manage\model\WebsiteLanguage;

class UploadController extends Controller
{
    public function upload()
    {
        header('Content-type: image/png');
        try {
            $filename = date('Ymdhis') . '_' . mt_rand(1000,9999);
            $fullname = $filename . '.jpg';
            $info = $_POST['info'];

            $file = fopen("upload/tinyMCE/images/". $fullname, "x");//打开文件准备写入
            fwrite($file, base64_decode($info));//写入
            fclose($file);//关闭

            $model = new ImageModel();
            $language = WebsiteLanguage::get(['status' => WebsiteLanguage::STATUS_ACTIVE, 'is_default' => WebsiteLanguage::DEFAULT_ACTIVE, 'is_avail' => WebsiteLanguage::AVIAIL_ACTIVE])->toArray();
            $data['Image'] = [
                'language_id'   =>  $language['id'],
                'cid'           =>  1,
                'title'         =>  $filename,
                'url'           =>  '/tinyMCE/images/' . $fullname,
                'status'        =>  1,
            ];
            $model->save($data['Image']);
            echo json_encode(['code' => 1, 'info' => "/upload/tinyMCE/images/". $fullname]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['code' => 0, 'info' => $e->getMessage()]);
            exit;
        }
    }

    public function image_upload()
    {
        header("content-type:text/html;charset=utf-8");
        $cid = $_POST['category_id'];
        $title = $_POST['title'];
        if (empty($cid) || $cid == 'undefined') {
            echo json_encode(['code' => 0, 'msg' => '请先选择分类']);
            exit();
        }
        if (empty($_FILES)) {
            echo json_encode(['code' => 0, 'msg' => '请先上传图片']);
            exit();
        }
        //设置时区
        date_default_timezone_set('PRC');
        //获取文件名
        $filename = $_FILES['file']['name'];
        //获取文件临时路径
        $temp_name = $_FILES['file']['tmp_name'];
        //获取大小
        $size = $_FILES['file']['size'];
        //获取文件上传码，0代表文件上传成功
        $error = $_FILES['file']['error'];
        if ($error) {
            echo json_encode(['code' => 0, 'msg' => '文件上传失败']);
            exit();
        }
        //判断文件大小是否超过设置的最大上传限制
        if ($size > 2 * 1024 * 1024){
            echo json_encode(['code' => 0, 'msg' => '文件大小超过2M大小']);
            exit();
        }
        //phpinfo函数会以数组的形式返回关于文件路径的信息 
        //[dirname]:目录路径[basename]:文件名[extension]:文件后缀名[filename]:不包含后缀的文件名
        $arr = pathinfo($filename);
        //获取文件的后缀名
        $ext_suffix = $arr['extension'];
        //设置允许上传文件的后缀
        $image_suffix = array('jpg','gif','jpeg','png');
        $video_suffix = array('mp4', 'avi');
        //判断上传的文件是否在允许的范围内（后缀）==>白名单判断
        if (in_array($ext_suffix, $image_suffix)) {
            $res_suffix = 'image';
        } elseif (in_array($ext_suffix, $video_suffix)){
            $res_suffix = 'video';
        } else {
            //window.history.go(-1)表示返回上一页并刷新页面
            echo json_encode(['code' => 0, 'msg' => '上传的文件类型只能是jpg,gif,jpeg,png']);
            exit();            
        }
        //检测存放上传文件的路径是否存在，如果不存在则新建目录
        if (!file_exists('upload/image-path/' . date('Ymd'))){
            mkdir('upload/image-path/' . date('Ymd'));
        }
        //为上传的文件新起一个名字，保证更加安全
        $default_title = date('YmdHis',time()).rand(100,1000);
        $new_filename = $default_title.'.'.$ext_suffix;
        //将文件从临时路径移动到磁盘
        if (move_uploaded_file($temp_name, 'upload/image-path/' . date('Ymd') . '/' . $new_filename)){
            if ((config('VIDEO_SAVE_SQL') && $res_suffix == 'video') || (config('IMAGE_SAVE_SQL') && $res_suffix == 'image')) {
                $model = new ImageModel();
                $language = WebsiteLanguage::get(['status' => WebsiteLanguage::STATUS_ACTIVE, 'is_default' => WebsiteLanguage::DEFAULT_ACTIVE, 'is_avail' => WebsiteLanguage::AVIAIL_ACTIVE])->toArray();
                $data['Image'] = [
                    'language_id'   =>  $language['id'],
                    'cid'           =>  $cid,
                    'title'         =>  empty($title) ? $default_title : $title,
                    'url'           =>  '/image-path/' . date('Ymd') . '/' . $new_filename,
                    'status'        =>  1,
                ];
                if ($model->save($data['Image'])) {
                    echo json_encode(['code' => 1, 'msg' => '文件上传成功', 'url' => $data['Image']['url'], 'ext' => $res_suffix]);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '文件上传失败']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 1, 'msg' => '文件上传成功', 'url' => '/image-path/' . date('Ymd') . '/' . $new_filename, 'ext' => $res_suffix]);
                exit; 
            }
        }else{
            echo json_encode(['code' => 0, 'msg' => '文件上传失败']);
            exit;
        }
    }

    public function file_upload()
    {
        header("content-type:text/html;charset=utf-8");
        // dump($_FILES);exit;
        
        if (empty($_FILES)) {
            echo json_encode(['code' => 0, 'msg' => '请先上传文件']);
            exit();
        }
        //设置时区
        date_default_timezone_set('PRC');
        //获取文件名
        $filename = $_FILES['file']['name'];
        //获取文件临时路径
        $temp_name = $_FILES['file']['tmp_name'];
        //获取大小
        $size = $_FILES['file']['size'];
        //获取文件上传码，0代表文件上传成功
        $error = $_FILES['file']['error'];
        if ($error) {
            echo json_encode(['code' => 0, 'msg' => '文件上传失败']);
            exit();
        }
        //判断文件大小是否超过设置的最大上传限制
        if ($size > 10 * 1024 * 1024){
            echo json_encode(['code' => 0, 'msg' => '文件大小超过10M']);
            exit();
        }
        //phpinfo函数会以数组的形式返回关于文件路径的信息 
        //[dirname]:目录路径[basename]:文件名[extension]:文件后缀名[filename]:不包含后缀的文件名
        $arr = pathinfo($filename);
        //获取文件的后缀名
        $ext_suffix = $arr['extension'];
        //设置允许上传文件的后缀
        $suffix = config('FILES_EXT');
        //判断上传的文件是否在允许的范围内（后缀）==>白名单判断
        if (!in_array($ext_suffix, $suffix)) {
            //window.history.go(-1)表示返回上一页并刷新页面
            echo json_encode(['code' => 0, 'msg' => '上传了不支持的文件类型']);
            exit();            
        }
        //检测存放上传文件的路径是否存在，如果不存在则新建目录
        if (!file_exists('upload/excel')){
            mkdir('upload/excel');
        }
        //为上传的文件新起一个名字，保证更加安全
        $default_title = date('YmdHis',time()).rand(100,1000);
        $new_filename = $default_title.'.'.$ext_suffix;
        //将文件从临时路径移动到磁盘
        if (move_uploaded_file($temp_name, 'upload/excel/' . $new_filename)){
            echo json_encode(['code' => 1, 'msg' => '文件上传成功', 'data' => $new_filename, 'origin' => $arr['filename']]);
            exit;
        }else{
            echo json_encode(['code' => 0, 'msg' => '文件上传失败']);
            exit;
        }
    }
}
