host = 'http://'+window.location.host+'/';
layui.use(['layer', 'jquery', 'upload','form','laypage'],
	function() {
		var $ = layui.jquery;
		var layer = layui.layer;
		var upload = layui.upload;
		var form = layui.form;
        var laypage = layui.laypage;
		var index = parent.layer.getFrameIndex(window.name);

		var category;
		var category_id;
		var image_id;
		// 初始化分类下拉列表
		// $.ajax({
        //     type: 'POST',
        //     url: "/Manage/imageCategory/get_category",
        //     data: '',
        //    	async: false,
        //     success: function(result) {
        //     	if (result.code == 1) {
		// 			category = '<div class="layui-form-item">'
		// 				+ '<label class="layui-form-label">分类</label>'
		// 				+ '<div class="layui-input-block" style="line-height:3">'
		// 				+ '<select name="category" lay-filter="category">'
		// 				+ '<option value="">请选择</option>';
		// 			for (var i = 0; i <= result.info.length - 1; i++) {
		// 				category += '<option value="' + result.info[i].id + '">' + result.info[i].name + '</option>';
		// 			}
		// 			category += '</select></div></div>';
		// 			$(".form-input").after(category);
		// 			form.render();
        //     	}
        //     }
        // });
		// 获取当前被选中的分类
		form.on('select', function (data) {
			category_id = data.value;
		});

		// 图片上传前选择分类
		$(".YanNanQiu_SubUploaderall").click(function(){
			layer.open({
				type: 1,
				title: '请先选择分类',
				content: $("#form-select"),
				area: ['400px', '410px'],
				btn: ['确定', '取消'],
				yes: function(index, layero){
					$(".YanNanQiu_SubUploaderall_img").click();
					layer.close(index);
				},
				btn2: function(index, layero){

				}
			});
		});
        
		//图片上传
		upload.render({
		    elem: '.YanNanQiu_SubUploaderall_img',
		    url: host + 'Manage/upload/image_upload', //改成您自己的上传接口
			accept: 'images', //普通文件
			acceptMime: 'image/*',
		    size: 0,
		    multiple: true,
		    before: function(obj){
                layer.load(0, {time: 3000}); //上传loading
            	this.data = {'category_id': category_id, 'title' : $("#form-title").val()};
            },
		    done: function(res){
		        layer.msg('图片上传中...', {icon: 16, shade: 0.01, time: 1000},function(){
					if(res.code == 1){
						layer.msg(res.msg,{icon: 1, time: 1500},function(){
							window.location.reload();
						})
					}else{
						layer.msg(res.msg);
					}
		        });
		    }
		});
        
        // 弹出图片页面
		$('.YanNanQiu_imgViews').click(function(){
			var imgurl = $(this).attr('data-url');
			var html;
			    html = '<div class="YanNanQiu_imglayer">';
				html +='<img src="'+imgurl+'">';
				html +='</div>';
			layer.open({
			  type: 1,
			  title: false,
			  closeBtn: 1,
			  shadeClose: true,
			  area: ['50%','auto'],
			  content: html
			});
		})

        // 弹出图片修改
		$('.YanNanQiu_imgEdits').click(function(){
			image_id = $(this).data('id');
			// 初始化图片原数据
			$.ajax({
	            type: 'POST',
	            url: '/Manage/image/get_image_info',
	            data: {id: image_id},
	           	async: false,
	            success: function(result) {
	            	$("#form-edit input[name='title']").val(result.info.title);
					$("#form-edit select").val(result.info.cid);
		        	form.render();
					$(".layui-this").click();
	            }
	        });
			layer.open({
				type: 1,
				title: '图片修改',
				content: $("#form-edit"),
				area: ['400px', '410px'],
				btn: ['确定', '取消'],
				yes: function(index, layero){
					$("#form-submit").click();
					layer.close(index);
				},
				btn2: function(index, layero){

				}
			});
		});
		// 监听图片修改提交按钮
		form.on('submit(formSubmit)', function(data){
			data.field.id = image_id;
			$.ajax({
	            type: 'POST',
	            url: '/Manage/image/edit',
	            data: data.field,
	           	async: false,
	            success: function(result) {
	            	if (result.code == 1) {
	            		layer.msg(result.msg, {icon: 1, time: 1500}, function(){
							window.location.reload();
	            		});
	            	} else {
						layer.msg(result.msg);
	            	}
	            }
	        });
		});

		//全选按钮	
		form.on('checkbox(YanNanQiu_checkall)', function(data) {
			var a = data.elem.checked;
			console.log(a);
			if (a == true) {
				$(".YanNanQiu_imgId").prop("checked", true);
				$('.YanNanQiu_Checkbox').addClass('CheckActive');
				$('.YanNanQiu-UploaderList li').addClass('CheckActiveLi');
				form.render('checkbox');
			} else {
				$(".YanNanQiu_imgId").prop("checked", false);
				$('.YanNanQiu_Checkbox').removeClass('CheckActive');
				$('.YanNanQiu-UploaderList li').removeClass('CheckActiveLi');
				form.render('checkbox');
			}

		});

		//有一个未选中全选取消选中
		form.on('checkbox(imgbox)', function(data) {
			var item = $(".YanNanQiu_imgId");
			for (var i = 0; i < item.length; i++) {
				if (item[i].checked == false) {
					$("#YanNanQiu_checkall").prop("checked", false);
					form.render('checkbox');
					break;
				}
			}
			//如果都勾选了  勾上全选
			var all = item.length;
			for (var i = 0; i < item.length; i++) {
				if (item[i].checked == true) {
					all--;
				}
			}
			if (all == 0) {
				$("#YanNanQiu_checkall").prop("checked", true);
				$('.YanNanQiu_Checkbox').addClass('CheckActive');
				$('.YanNanQiu-UploaderList li').addClass('CheckActiveLi');
				form.render('checkbox');
			}
		});
		
        //单个选中
        form.on('checkbox(imgbox)', function(data){
            var b = data.elem.checked;
			if (b == true) {
               $(this).parent().eq($(this).index()).addClass('CheckActive');
               $(this).parent().parent().eq($(this).index()).addClass('CheckActiveLi');
            }else{
                $(this).parent().eq($(this).index()).removeClass('CheckActive');
                $(this).parent().parent().eq($(this).index()).removeClass('CheckActiveLi');
            } 
            form.render('checkbox');
        });
      
        // 批量删除图片
	    form.on('submit(DelImgAll)', function(data){
	    	layer.confirm('删除图片可能会对其他使用该图片的场景造成影响，您确认要删除吗?', {icon: 3, title:'提示'}, function(index){
				//do something
				delete data.field.file;
				var url = host + 'Manage/image/delete';
				var $checkbox = $('.YanNanQiu-UploaderList input[type="checkbox"]');
				if ($checkbox.is(":checked")) {
					$.ajax({
			            type: 'POST',
			            url: url,
			            data: data.field,
			           	async: false,
			            success: function(result) {
							layer.msg('正在删除中...', {icon: 16, time: 1000}, function(){
				            	if (result.code == 1) {
				            		layer.msg(result.msg, {icon: 1, time: 1500}, function(){
										window.location.reload();
				            		});
				            	} else {
									layer.msg(result.msg);
				            	}
			        		});
			            }
			        });
				} else {
					layer.msg('请选择要删除的图片');
				}
			});
			return false;
		 });


        // 弹出式图片管理
		// $('.YanNanQiu_layerImg').on('click','li',function(){
		// 	var imgpath = $(this).find('img').attr('src');
		// 	var imgpathArr = new Array();

		// 	if(!$(this).hasClass("YanNanQiu_ImgActive")){
		// 	   $(this).addClass("YanNanQiu_ImgActive");
		// 	   $(this).next().find('input[type="checkbox"]').prop("checked", true);
		// 	 }else{
		// 	    $(this).removeClass("YanNanQiu_ImgActive");
		// 		$(this).find('input[type="checkbox"]').prop("checked", true);
		// 	 }
			 
		// 	$(".YanNanQiu_ImgActive").each(function(){
		// 	imgpathArr.push($(this).find('img').attr('src'));
		// 	});
		// 	$('.YanNanQiu_ImgPaths').val(imgpathArr);
		// })

		$('.YanNanQiu_layerImg').click(function(){
			var imgpath = $(this).find('img').attr('src');
			var imgpathArr = new Array();

			if(!$(this).hasClass("YanNanQiu_ImgActive")){
			   $(this).addClass("YanNanQiu_ImgActive");
			   $(this).next().find('input[type="checkbox"]').prop("checked", true);
			 }else{
			    $(this).removeClass("YanNanQiu_ImgActive");
				$(this).find('input[type="checkbox"]').prop("checked", true);
			 }
			 
			$(".YanNanQiu_ImgActive").each(function(){
			imgpathArr.push($(this).find('img').attr('src'));
			});
			$('.YanNanQiu_ImgPaths').val(imgpathArr);
		});

        
        //从库中选择
		$('.YanNanQiu_ViewsUploader').click(function(){
			var indext = layer.open({
				title: '在线图片管理',
				closeBtn: 1,
				type: 1,
				shadeClose: true,
				shade: 0.8,
				area: ['50%','69%'],
				maxmin: false,
				content: $("#imageManage"),
				btn: ['确定', '取消'],
				yes: function(index, layero){
				   var imgsize = 6;
				   var body = layer.getChildFrame('body', index);
				   var imgpaths = body.contents().find("#YanNanQiu_ImgPaths").val();
				   layer.close(indext);
				   var imgpathss = imgpaths.split(',');
				   var uploaderHtml='';
				   //获取上传图片的长度
					 $.each(imgpathss,function(i,val){
						uploaderHtml += '<li>';
						uploaderHtml += '<img src="'+ val +'">';
						uploaderHtml += '<span><i class="fa fa-times"></i></span>';
						uploaderHtml += '<input type="text" name="img[]" value="'+ val +'">';
						uploaderHtml += '</li>'; 
					 }) ;
		             $('.YanNanQiu-upload-list').append(uploaderHtml); 
				}
			});
		})

        //缩略图上传
    	var uploadInst = upload.render({
    		elem: '.YanNanQiu_UploaderImg', //绑定元素
    		url: host+'action/upload?action=upload&type=image&btntype=upload',
    		method: 'get',
    		accept: 'images', //普通文件
    		acceptMime: 'image/*',
    		done: function(img) {
    			if (img.code == 1) {
    				$html = '<img src="'+img.src+'" alt="">';
    				$('.YanNanQiu_UploaderImg').html($html);
					$('.YanNanQiu_fangboxUpload span').attr('data-url',img.src);
					$('.YanNanQiu_TempImg').val(img.src);
    			} else {
    				layer.msg(img.msg, {
    					time: 3000
    				});
    			}
    	
    		}
    	});
        
		//删除缩略图片
		$('.YanNanQiu_fangboxUpload').on('click','span',function(){
			$('.YanNanQiu_UploaderImg img').remove();
			$('.YanNanQiu_UploaderImg').html('<i class="fa fa-picture-o"></i><em>上传缩略图</em>');
			$('.YanNanQiu_TempImg').val('');
		})

        //数字转有逗号，小数点
		$("[number]").blur(function(){
			//获取页面值
			    var str = $(this).val();
			//若是整数自动补全小数位
			    if (-1 == str.indexOf(".")) {
			        str = str + ".00"
			    }
			//全部替换
			    if (-1 != str.indexOf(",")) {
			        str = str.replace(new RegExp(',', "g"), "")
			    }
			    var intSum = str.substring(0, str.indexOf(".")).replace(/\B(?=(?:\d{3})+$)/g, ',');//取到整数部分
			    var dot = str.substring(str.length, str.indexOf("."))//取到小数部分搜索
			    var ret = intSum + dot;
			//值重新填充到页面
			    $(this).val(ret);
		})
	});


  