layui.use(["table", "carousel", "layer", "form", "jquery", "element"], function() {
	var table = layui.table;
	var $ = layui.jquery;
	var carousel = layui.carousel;
	var layer = layui.layer;
	var form = layui.form;
	var element = layui.element;
	
	//返回顶部
	$(window).scroll(function() {
			var scrollY = $(document).scrollTop();
			if (scrollY > 500) {
				$('.ynq-gotop').fadeIn(300);
				$('.ynq-gotop2').fadeIn(300);
			} else {
				$('.ynq-gotop').fadeOut(300);
				$('.ynq-gotop2').fadeOut(300);
			}
		});
	
	$(".ynq-gotop2").click(function() {
			$("html,body").animate({
				"scrollTop": 0
			}, 500);
		});
    
	//搜索
	$('.ynq-search-btn a').click(function(){
		$('.ynq-header-search').slideDown(300);
	});
	
	$("body,html").click(function(e){
			var target = $(e.target);
			if(target.closest(".ynq-search-btn a,.ynq-header-search").length != 0) return;
			$(".ynq-header-search").fadeOut(300);
	})
	//手机版搜索
	$('.ynq-mob-search a').click(function(){
		$('.ynq-mob-searchform').slideToggle(300);
	})
	
	
	//手机版效果
	$("body,html").click(function(e){
			var target = $(e.target);
			if(target.closest(".ynq-mob-search a,.ynq-mob-searchform").length != 0) return;
			$(".ynq-mob-searchform").fadeOut(300);
	});
	
	//手机端导航菜单
	$('.ynq-mob-nav').click(function(){
		$('.ynq-mob-navlist').animate({right:'-10px'}).toggle(300);
	})
	
	//自适应高度轮播
	var b = 1920/734;//我的图片比例
	 var W = $(window).width();
	 var H = $(window).height();
	 carousel.render({
	    elem: '#ynq-indexSwiper'
	    ,interval: 2800
	    ,anim: 'fade'
		,width:'100%'
	    ,height: (W/b).toString()+"px"
	  });
	  
	//窗口变化是重新加载
	$(window).resize(function () {
	 // setBanner();
	 window.location.reload()
	})
	
	//首页第一个下拉选项跳转
	form.on('select(ynq-haoyou)', function(data){
		url = data.value;
		if(url.substr(0,7).toLowerCase() == "http://" || url.substr(0,8).toLowerCase() == "https://"){
		    url = url;
			window.open(url);
		}else{
		    layer.alert('请选择');
        }
	});
	
	//首页第二个下拉选项跳转
	form.on('select(ynq-jinwai)', function(data){
		url = data.value;
		if(url.substr(0,7).toLowerCase() == "http://" || url.substr(0,8).toLowerCase() == "https://"){
		    url = url;
			window.open(url);
		}else{
		    layer.alert('请选择');
	    }
	});
	
	//首页第三个下拉选项跳转
	form.on('select(ynq-zhenwu)', function(data){
		url = data.value;
		if(url.substr(0,7).toLowerCase() == "http://" || url.substr(0,8).toLowerCase() == "https://"){
		    url = url;
			window.open(url);
		}else{
		    layer.alert('请选择');
	    }
	});
	
});