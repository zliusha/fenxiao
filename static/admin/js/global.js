
var CSRF_ID = 'csrf_cookie_name';
var SESSION_ID = 'admin_id';
function autoCsrf(params)
{   
    if(params==undefined)
        params={};
    var autoParams={csrf_token_name:Cookie.GetByName(CSRF_ID),rdm:Math.random()};
    return $.extend(autoParams,params);
}
function getHeight() {
        return $(window).height() - 40;
}
(function(a){
    a.btTable={
        id:"bt_table"
        ,pageConfig:{
            toolbar:"#toolbar"
            ,height:getHeight()
            ,search:true
            ,showRefresh:true
            ,showToggle:true
            ,showColumns:true
            ,minimumCountColumns:2
            ,showPaginationSwitch:true
            ,pagination:true
            ,idField:"id"
            ,pageList:[10, 25, 50, 100]
            ,showFooter:false
            ,sidePagination:"server"
            ,striped:true
            ,sortName:"id"
            ,sortOrder:"desc"
            ,searchOnEnterKey:true
            ,strictSearch:true
        }
        ,init:function(bt_options,id)
        {
            var bt_id= id ? id : btTable.id;
            $table = $("#"+bt_id);
            if($table != undefined)
            {    
                bt_options = bt_options ? bt_options : {};
                var options = $.extend({onEditableSave:window.field_update},btTable.pageConfig,bt_options);
                $table.bootstrapTable(options);
                //fix table window
                $(window).resize(function () {
                    $table.bootstrapTable('resetView', {
                    height: getHeight()
                    });
                });
                $btn_search=$(".gd-search-btn");
                if($btn_search != undefined)
                {
                    btTable.init_search();
                }
            }
        }
        ,refresh:function(params,id)
        {
            var bt_id=id?id:btTable.id;
            $table = $("#"+bt_id);
            if($table != undefined)
            {    
                $table.bootstrapTable('refresh',params);
            }
        },
        updateCell:function(index,field,value,id)
        {
            var bt_id=id?id:btTable.id;
            $table = $("#"+bt_id);
            if($table != undefined)
            {    
                console.log("index:"+index);
                var params={"index":index,"field":field,"value":value};
                $table.bootstrapTable('updateCell',params);
            }
        },
        resetSearch:function(w,id)
        {
            var bt_id=id?id:btTable.id;
            $table = $("#"+bt_id);
            if($table != undefined)
            {    
                $table.bootstrapTable('resetSearch',w);
            }
        },
        init_search:function()
        {
            $(".gd-search-dropdown>ul>li>a").click(function(){
                $(".gd-search-dropdown>.gd-search-dropdwon-selected").attr('field',$(this).attr('field')).html($(this).text()+"<span class=\"caret\"></span>");
            });
            $(".gd-search-btn").click(function(){
                var $selected=$(".gd-search-dropdown>.gd-search-dropdwon-selected");
                var $txt=$('.gd-search-txt');
                var w='';
                if($selected!=undefined && $txt!=undefined)
                {
                    var name=$selected.attr('field');
                    var value=$.trim($('.gd-search-txt').val());
                    var reg = /[|]/g;
                    value = value.replace(reg, "");
                    w = name + "|" + value;
                }
                if(window['ext_search']!=undefined)
                {
                    var ext_w=$.trim(window['ext_search']());
                    if(ext_w!="")
                    {
                        if(w=='')
                        w=ext_w;
                        else
                        w+="||"+ext_w;
                    }
                }
                btTable.resetSearch(w);
            });
        }
    };

})(window);

$(function(){
    //初始化
    if(window.bt_options)
    {
        btTable.init(window.bt_options);
    }
    else
    {
        btTable.init();
    }

    $(".ibox-fluid").css('min-height',getHeight()+"px");
    $(window).resize(function () {
                    $(".ibox-fluid").css('min-height',getHeight()+"px");
                });
});