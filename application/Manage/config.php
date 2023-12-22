<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 后台登录用户
    'USER_LOGIN_FLAG'   =>  'user_login_flag',
    'USER_LOGIN_TIME'   =>  'user_login_time',
    'USER_MENU'         =>  'user_menu',

    // 后台上一页地址
    'BACK_URL'			=>	'back_url',

    // 后台分页设置
   	'PAGE_NUM'			=>	15,

    // 后台文件上传配置
    'IMAGE_EXT'         =>  ['jpg', 'gif', 'jpeg', 'png'],          // 图片限制后缀
    'VIDEO_EXT'         =>  ['mp4', 'avi'],                         // 媒体限制后缀
    'FILES_EXT'         =>  ['zip', 'rar' ,'7z', 'jpg', 'gif', 'jpeg', 'png', 'mp4', 'avi', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'ico', 'pdf'],                                // 文件限制后缀
    'IMAGE_SAVE_SQL'    =>  true,                                   // 默认把图片保存到库
    'VIDEO_SAVE_SQL'    =>  false,                                  // 默认不把视频保存到库

    // 后台产品配置
    'PRODUCT_IMAGE'     =>  6,                                      // 产品图片数量上限
    'LOWER_CATEGORY'    =>  true,                                   // 是否开启产品分类只能为底级菜单

    // 权限列表不需要验证的控制器、方法名称
    'ACCESS_CONTROLLER' =>  ['login', 'index', 'check', 'upload'],            // 必须小写
    'ACCESS_ACTION'     =>  [
        'site/admin',
        'site/repass',
        'image/get_category_images',
        'image/get_image_info',
        'imagecategory/get_category',
        'blockchild/get_template_banner',
        'info/get_infos',
        'info/get_info_content',
    ],                                                              // 必须小写

    // TinyMCE模块、插件
    'TINYMCE_PLUGINS'   =>  'print preview searchreplace autolink directionality visualblocks visualchars image link media template code codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists wordcount imagetools textpattern help emoticons axupimgs lineheight',
    'TINYMCE_TOOLBAR'   =>  'removeformat code undo redo | forecolor backcolor bold italic underline strikethrough link table imagetools image media anchor | alignleft aligncenter alignright alignjustify | styleselect | fontselect | fontsizeselect | lineheight | bullist numlist | blockquote subscript superscript removeformat | charmap emoticons hr pagebreak insertdatetime print preview',
];
