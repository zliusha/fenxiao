/**
 * elm.js
 * by shaoyu
 * date: 2018-4-17
 */
$(function () {
    var crawlerTpl = document.getElementById('crawlerTpl').innerHTML,
    eleTpl = document.getElementById('eleTpl').innerHTML,
    $changeShopModal = $('#changeShopModal'),
    $btnConfirm = $('#btn-confirm');

    // 搜索地址
    function searchVal() {
        var value = $('#searchVal').val();
        if(value==''){
            new Msg({
                type: "danger",
                msg: '请输入对应的值'
            });
            return false;
        }
        $.getJSON(__BASEURL__ + 'elm_api/get_address', {
          address:value,
        }, function (data) {
          if (data.success) {
            var data =  JSON.parse(data.data)
            var data2 = {
              data:data
            }
            console.info(data2)
            $('#eleTbody').html(template(eleTpl, data2));
          }else{
            new Msg({
              type: "danger",
              msg: data.msg
            });
          }
        });
    }

    $('#btn-search').click(function(){
        searchVal();
    })

    // 选择弹窗
      function changeShop(address, latitude, longitude) {
        $changeShopModal.modal('show').on('shown.bs.modal', function () {
          $btnConfirm.data('address', address);
          $btnConfirm.data('latitude', latitude);
          $btnConfirm.data('longitude', longitude);
        });
      }

  $btnConfirm.on('click', function () {
    var address = $(this).data('address');
    var latitude = $(this).data('latitude');
    var longitude = $(this).data('longitude');
    $btnConfirm.prop('disabled', true);
    $.post(__BASEURL__ + 'elm_api/get_business_info', autoCsrf({
      address:address,
      latitude:latitude,
      longitude:longitude
    }), function (data) {
      if (data.success) {
        new Msg({
          type: "success",
          msg: data.msg
        });
        showCrawler()
      }else{
        new Msg({
          type: "danger",
          msg: data.msg
        });
      }
        $btnConfirm.prop('disabled', false);
        $changeShopModal.modal('hide');
    });
  });



    //展示当前用户的爬虫记录
    function showCrawler() {
        $.getJSON(__BASEURL__ + 'elm_api/show_crawler_log', {
  
        }, function (data) {
          if (data.success) {
            console.info(data)
            $('#crawlerTbody').html(template(crawlerTpl, data));
          }else{
            new Msg({
              type: "danger",
              msg: data.msg
            });
          }
        });
    }

    showCrawler()


    window.changeShop = changeShop;
});