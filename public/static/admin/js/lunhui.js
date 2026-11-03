var lunhui = {

	//成功弹出层
	success: function(message,url){
		layer.msg(message, {icon: 1,time:1500}, function(index){
            layer.close(index);
            window.location.href=url;
        });
	},

	// 错误弹出层
	error: function(message) {
        layer.msg(message, {icon: 2,time:1500}, function(index){
            layer.close(index);
        });       
    },
    //成功弹出层手机端
	success_mobile: function(message,url){
		layer.open({
							content: message,
							btn: '我知道了',
							time: 30 //2秒后自动关闭
							,yes: function(index){						      
						       window.location.href=url;
						    }
				});	
		
	},

	// 错误弹出层手机端
	error_mobile: function(message) {
        layer.open({
							content: message,
							btn: '我知道了',
							time: 30 //2秒后自动关闭
							
				});	       
    },

	// 确认弹出层
    confirm : function(id,url) {
        layer.confirm('确认删除此条记录吗?', {icon: 3, title:'提示'}, function(index){
	        $.post(url, {'id' : id}, function(res){
	            if(res.code == 1){
	                layer.msg(res.msg,{icon:1,time:1000,shade: 0.1});
	                Ajaxpage()
	            }else{
	                layer.msg(res.msg,{icon:0,time:1000,shade: 0.1});
	            }
	        }, 'json');
	        layer.close(index);
	    })
    },
		// 确认弹出层
    confirma : function(id,mpid,url) {
        layer.confirm('确认删除此条记录吗?', {icon: 3, title:'提示'}, function(index){
	        $.post(url, {'id' : id}, function(res){
	            if(res.code == 1){
	                layer.msg(res.msg,{icon:1,time:1000,shade: 0.1});
	                location.href = "{:config('is_https')}://"+window.location.host+'/admin/category/edit_member/id/'+mpid+'.html';
	            }else{
	                layer.msg(res.msg,{icon:0,time:1000,shade: 0.1});
	            }
	        }, 'json');
	        layer.close(index);
	    })
    },	
    // 确认弹出层
    confirmattach : function(id,groupid,url) {
        layer.confirm('确认删除此条记录吗?', {icon: 3, title:'提示'}, function(index){
	        $.post(url, {'id' : id}, function(res){
	            if(res.code == 1){
	                layer.msg(res.msg,{icon:1,time:1000,shade: 0.1});
	                location.href = "{:config('is_https')}://"+window.location.host+'/admin/attach/edit_group/id/'+groupid+'.html';
	            }else{
	                layer.msg(res.msg,{icon:0,time:1000,shade: 0.1});
	            }
	        }, 'json');
	        layer.close(index);
	    })
    },
    //状态
    status : function(id,url){
	    $.post(url,{id:id},function(data){	         
	        if(data.code==1){
	            var a='<span class="label label-danger">禁用</span>'
	            $('#zt'+id).html(a);
	            layer.msg(data.msg,{icon:2,time:1000,shade: 0.1,});
	            return false;
	        }else{
	            var b='<span class="label label-info">开启</span>'
	            $('#zt'+id).html(b);
	            layer.msg(data.msg,{icon:1,time:1000,shade: 0.1,});
	            return false;
	        }         	        
	    });
	    return false;
	},
	 //分销状态
    distribut : function(id,url){
	    $.post(url,{id:id},function(data){	         
	        if(data.code==1){
	            var a='<span class="label label-danger">否</span>'
	            $('#distribut'+id).html(a);
	            layer.msg(data.msg,{icon:2,time:1000,shade: 0.1,});
	            return false;
	        }else{
	            var b='<span class="label label-info">是</span>'
	            $('#distribut'+id).html(b);
	            layer.msg(data.msg,{icon:1,time:1000,shade: 0.1,});
	            return false;
	        }         	        
	    });
	    return false;
	},
    //状态
    tuijian: function(id,url){
	    $.post(url,{id:id},function(data){	         
	        if(data.code==1){
	            var a='<span class="label label-default">不推</span>'
	            $('#tj'+id).html(a);
	            layer.msg(data.msg,{icon:2,time:1000,shade: 0.1,});
	            return false;
	        }else{
	            var b='<span class="label label-danger">推荐</span>'
	            $('#tj'+id).html(b);
	            layer.msg(data.msg,{icon:1,time:1000,shade: 0.1,});
	            return false;
	        }         	        
	    });
	    return false;
	},
    
    //爆款促销
    hot:function(id,url){
	    $.post(url,{id:id},function(data){	         
	        if(data.code==1){
	            var a='<span class="label label-default">非爆</span>'
	            $('#hot'+id).html(a);
	            layer.msg(data.msg,{icon:2,time:1000,shade: 0.1,});
	            return false;
	        }else{
	            var b='<span class="label label-warning">爆款</span>'
	            $('#hot'+id).html(b);
	            layer.msg(data.msg,{icon:1,time:1000,shade: 0.1,});
	            return false;
	        }         	        
	    });
	    return false;
	}



}