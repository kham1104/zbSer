var ybplay={
	closePorp:function()
	{
		$('#buyvip').hide();
        $('#buyvip').html("");
        document.getElementById('ds-dialog-bg').style.display='none';
	},
	player:function(id)
	{
		
		$.ajax({
          cache: true,
          type: "GET",
          url:'/home/playback/getCdnRecord',
          data:{id:id},
          async: false,
          error: function(request)
          {
                    layer.msg("数据请求失败");
          },
          success: function(data)
          {
                    if(data.code!=0)
                    {
                        layer.msg(data.msg);
                        return !1;
                    }
                    
                    $(".event").removeClass("selected");
                    var url=data.info;
                    url=url['url'];
                    ybplay.video(url);
                    $("#play_"+id).addClass("selected");

          }
        });
	},
	video:function(url)
	{
        xgPlay('play_reft',url);
	}
}
