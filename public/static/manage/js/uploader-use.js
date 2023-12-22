host = 'http://'+window.location.host+'/';
layui.use(['layer', 'jquery', 'upload','form','laypage'],
	function() {
		var $ = layui.jquery;
		var layer = layui.layer;
		var upload = layui.upload;
		var form = layui.form;
        var laypage = layui.laypage;
		var index = parent.layer.getFrameIndex(window.name);

		// 初始化弹窗内容
		var alert_window = '<div id="imageManage" style="display: none;width: 80%;margin: 0 auto; padding-top: 20px">'
			+ '<button id="btn-upload" style="display:none"></button>'
			+ '<form class="layui-form">'
			+ '<input type="hidden" name="ImgPaths" class="form-input" value="" id="YanNanQiu_ImgPaths"></input>'
        	+ '<div class="form-input"></div>'
        	+ '<section class="YanNanQiu_page" data-content="弹窗式图片管理">'
        	+ '<ul class="YanNanQiu_layerImg">';
    	$.ajax({
			type: 'POST',
			url: '/Manage/image/get_category_images',
			data: {'id': 0},
			async: false,
			success: function(result) {
				if (result.code == 1) {
					for (var i = 0; i <= result.info.length - 1; i++) {
						alert_window += '<li>'
							+ '<div class="YanNanQiu_layerImgbox">'
							+ '<img src="/upload' + result.info[i].url +'">'
							+ '</div>'
							+ '<div class="YanNanQiu_Checkbox">'
							+ '<input type="checkbox" name="" lay-skin="primary" lay-filter="imgbox" class="YanNanQiu_imgId" value="' + result.info[i].id + '">'
							+ '</div>'
							+ '</li>';
					}
					alert_window += '</ul></section></form></div>';
					$('body').after(alert_window);
					form.render();
				}
			}
		});

		// 初始化分类下拉列表
		var category;
		var category_id;
		var image_id;
		var input_name;
		var image_num;
		var btn;
		$.ajax({
            type: 'POST',
            url: '/Manage/imageCategory/get_category',
            data: '',
           	async: false,
            success: function(result) {
            	if (result.code == 1) {
	            	category = '<div class="layui-form-item"><label class="layui-form-label">分类</label><div class="layui-input-block" style="line-height:3"><select name="category" lay-filter="category"><option value="">请选择</option>';
	            	for (var i = 0; i <= result.info.length - 1; i++) {
	            		category += '<option value="' + result.info[i].id + '">' + result.info[i].name + '</option>';
	            	}
	            	category += '</select></div></div>';
	            	$(".form-input").append(category);
	            	form.render();
            	}
            }
        });
        
        // 弹窗选择或本地上传
		$('.YanNanQiu_ViewsUploader').click(function(){
			btn = $(this);
			input_name = $(this).attr('name');
			image_num = $(this).data('num') ? $(this).data('num') : 1 
			var indext = layer.open({
				title: '图片上传',
				closeBtn: 1,
				type: 1,
				shadeClose: true,
				shade: 0.8,
				area: ['830px','600px'],
				maxmin: false,
				content: $("#imageManage"),
				btn: ['确定', '取消', '本地上传'],
				yes: function(index, layero){
					var imgpaths = $("#YanNanQiu_ImgPaths").val();
					if (imgpaths == '') {
						layer.msg('请选择你要上传的图片', {time: 1000},function(){
							// layer.close(indext);
				        });
						return false;
					}
					var imgpathss = imgpaths.split(',');
					var image_lu = btn.next().find('li');
					if (image_lu.length + imgpathss.length > image_num) {
						layer.msg('至多上传' + image_num + '张', {time: 1000},function(){
							// layer.close(indext);
				        });
						return false;
					}
					layer.close(indext);
					var uploaderHtml='';
					//获取上传图片的长度
					$.each(imgpathss,function(i,val){
						uploaderHtml += '<li style="margin: 2px">';
						uploaderHtml += '<img src="/upload/'+ val +'">';
						uploaderHtml += '<span><i class="fa fa-times"></i></span>';
						uploaderHtml += '<input type="hidden" name="' + input_name + '[]" value="/'+ val +'">';
						uploaderHtml += '</li>';
					}) ;
					btn.next().append(uploaderHtml);
					$('.YanNanQiu_page li').removeClass("YanNanQiu_ImgActive");
					form.render();
				},
				btn2: function(index, layero){
					layer.close(index);
					return false;
				},
				btn3: function(index, layero){
					var image_lu = btn.next().find('li');
					if (image_lu.length >= image_num) {
						layer.msg('至多上传' + image_num + '张', {time: 1000},function(){
							// layer.close(indext);
				        });
						return false;
					} else {
						$("#btn-upload").click();
						layer.close(index);
					}
					return false;
				}
			});
		});

		//图片上传
		upload.render({
			elem: '#btn-upload',
			url: host + 'Manage/upload/image_upload', //改成您自己的上传接口
			accept: 'file' , //普通文件
			acceptMime: 'image/*, video/*',
			size: 0,
			multiple: true,
			before: function(obj){
				// layer.load(); //上传loading
				this.data = {'category_id': 1, 'title' : ''};
			},
			done: function(res){
				layer.msg('图片上传中...', {icon: 16, shade: 0.01, time: 1000},function(){
					if(res.code == 1){
						if (res.ext == 'image') {
							var img_url = '/upload' + res.url;
						} else {
							var img_url = '/static/images/video-default.png';
						}
						var uploaderHtml='';
						uploaderHtml += '<li style="margin: 2px">';
						uploaderHtml += '<img src="'+ img_url +'">';
						uploaderHtml += '<span><i class="fa fa-times"></i></span>';
						uploaderHtml += '<input type="hidden" name="' + input_name + '[]" value="'+ res.url +'">';
						uploaderHtml += '</li>';
						btn.next().append(uploaderHtml);
						form.render();
						layer.msg(res.msg,{icon: 1, time: 1500});
					}else{
						layer.msg(res.msg);
					}
				});
			}
		});

		// 监听选择分类时的图片数据
		form.on("select", function(data){
            $.ajax({
	            type: 'POST',
	            url: "/Manage/image/get_category_images",
	            data: {id: data.value},
	           	async: false,
	            success: function(result) {
	            	if (result.code == 1) {
	            		var string = '';
	            		for (var i = 0; i <= result.info.length - 1; i++) {
	            			result.info[i]
	            			string += '<li>'
	            				+ '<div class="YanNanQiu_layerImgbox">'
	            				+ '<img src="/upload' + result.info[i].url + '">'
	            				+ '</div>'
	            				+ '<div class="YanNanQiu_Checkbox">'
	            				+ '<input type="checkbox" name="' + input_name + '[]" lay-skin="primary" lay-filter="imgbox" class="YanNanQiu_imgId" value="' + result.info[i].id + '">'
	            				+ '</div>'
	            				+ '</li>';
	            		}
	            		$("#imageManage").find('ul').html(string);
	            		form.render();
	            	} else {
	            		$("#imageManage").find('ul').html('');
	            		form.render();
	            	}
	            }
	        });
    	});

		// 图片选中样式
		$('.YanNanQiu_layerImg').on('click','li',function(){
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
				imgpathArr.push($(this).find('img').attr('src').substring(8));
			});
			$('#YanNanQiu_ImgPaths').val(imgpathArr);
		});

		// 删除上传图片
		$('.YanNanQiu-upload-list').on('click','span',function(){
		var imgpath = $(this).siblings().attr('src');
			$(this).parent().remove();
		});

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


  