$(function() {
  // 清除点击延迟
  FastClick.attach(document.body);
  var cart = new Cart();
  var index = 0;
  var good_amount = 0;
  // 商品轮播图
  function swiper() {
    var swiper = new Swiper(".swiper-container", {
      preloadImages: false,
      pagination: ".swiper-pagination",
      loop: true, //轮播图循环
      grabCursor: false,
      lazyLoading: true,
      paginationClickable: true, // 原点点击切换
      autoplayDisableOnInteraction: false //手指滚过后继续轮播
    });
  }

  // //切换详情和评价
  // var $navItem = $('.m-nav-item'),
  //     $shopSummary = $('.shop-summary'),
  //     $shopEvaluate = $('.shop-evaluate');
  // $navItem.click(function(){
  //   $(this).children().addClass('active');
  //   $(this).siblings().children().removeClass('active');
  //   if($('.m-nav-link.active').html()=='商品详情'){
  //     $shopSummary.show();
  //     $shopEvaluate.hide();
  //   }else{
  //     $shopEvaluate.show();
  //     $shopSummary.hide();
  //   }
  // })

  var goods_id = $("#goods_id").val(),
    shop_id = $("#shop-id").val(),
    goodData = "";

  function getGoodInfo() {
    $.getJSON(
      __BASEURL__ + "api/items/goods_info", {
        goods_id: goods_id,
        shop_id: shop_id
      },
      function(data) {
        if (data.success) {
          goodData = data.data;
          var picArr = data.data.picarr.split(",");
          picArr.pop();

          var html =
            '<div class="swiper-container">' + '<div class="swiper-wrapper">';
            if(data.data.video!=''){
              html+='<div class="swiper-slide" style="background:black">' +
            data.data.video+
              "</div>";
            }
          $.each(picArr, function(i, val) {
            html +=
              '<div class="swiper-slide" style="background: url(' +
              __UPLOADURL__ +
              picArr[i] +
              ') no-repeat center center;background-size: cover;">' +
              "</div>";
          });
          html +=
            "</div>" +
            '<div class="swiper-pagination"></div>' +
            "</div>" +
            '<div class="shop-box">' +
            '<div class="shop-info">' +
            '<h3 class="shop-title">' +
            data.data.title +
            "</h3>" +
            '<p class="shop-price-box f-flex clearfix">' +
            '<span class="shop-rmb f-flex-item">¥<span id="shop-price" class="shop-price">' +
            data.data.sku[0].sale_price +
            "</span></span>" +
            '<span id="shop-stock" class="shop-stock f-flex-item f-tac">库存：' +
            data.data.sku[0].use_stock_num +
            "</span>" +
            '<span id="shop-volume" class="shop-volume f-flex-item f-tar">销量：' +
            data.data.sku[0].sale_num +
            "</span>" +
            "</p>" +
            '<p class="shop-price-box f-flex clearfix">';
          if (data.data.sku_type == 1) {
            $.each(data.data.sku, function(i, val) {
              if (i == 0) {
                html += '<span class="cart-popup-spec active" onclick="changeSpec(this,' + i + ',' + data.data.sku[i].id + ',' + data.data.sku[i].sale_price + ',' + data.data.sku[i].use_stock_num + ',' + data.data.sku[i].sale_num + ')">' + data.data.sku[i].attr_names + '</span>';
              } else {
                html += '<span class="cart-popup-spec" onclick="changeSpec(this,' + i + ',' + data.data.sku[i].id + ',' + data.data.sku[i].sale_price + ',' + data.data.sku[i].use_stock_num + ',' + data.data.sku[i].sale_num + ')">' + data.data.sku[i].attr_names + '</span>';
              }
            })
          }
          if (data.data.promotion != null) {
            if (data.data.promotion.status == 2) {
              html +=
                "</p>" +
                "</div>";
              if (data.data.promotion != null) {
                html += '<div class="shop-activity f-flex">';
                if (data.data.promotion.type == 1) {
                  html += '<div class="shop-activity-left f-flex-item">满赠：</div>';
                } else if (data.data.promotion.type == 2) {
                  html += '<div class="shop-activity-left f-flex-item">满减：</div>';
                } else if (data.data.promotion.type == 3) {
                  html += '<div class="shop-activity-left f-flex-item">折扣：</div>';
                } else {
                  html += '<div class="shop-activity-left f-flex-item">限购：</div>';
                }

                var des = JSON.parse(data.data.promotion.setting);

                html += '<div class="f-flex-item">' +
                  '<div class="shop-activity-right-item f-flex">';
                if (data.data.promotion.type == 1) {
                  html += '<div class="shop-activity-label">' + data.data.promotion.title + '</div>';
                  if (des.condition_type == 1) {
                    html += '<div class="shop-activity-info">满' + des.condition_to_yuan + '元赠' + des.condition_gifts[0].title + '</div>';
                  } else {
                    html += '<div class="shop-activity-info">满' + des.condition_to_jian + '件赠' + des.condition_gifts[0].title + '</div>';
                  }
                } else if (data.data.promotion.type == 2) {
                  html += '<div class="shop-activity-label color-green">' + data.data.promotion.title + '</div>';
                  if (des.condition_type == 1) {
                    html += '<div class="shop-activity-info">满' + des.condition_to_yuan + '元减' + des.condition_money_off + '元</div>';
                  } else {
                    html += '<div class="shop-activity-info">满' + des.condition_to_jian + '件减' + des.condition_money_off + '元</div>';
                  }
                } else if (data.data.promotion.type == 3) {
                  html += '<div class="shop-activity-label color-violet">' + data.data.promotion.title + '</div>';
                  if (des.condition_type == 1) {
                    html += '<div class="shop-activity-info">满' + des.condition_to_yuan + '元打' + des.condition_discount + '折</div>';
                  } else {
                    html += '<div class="shop-activity-info">满' + des.condition_to_jian + '件打' + des.condition_discount + '折</div>';
                  }
                } else {
                  html += '<div class="shop-activity-label color-red">' + data.data.promotion.title + '</div>' +
                    '<div class="shop-activity-info">限购<span id="restriction">' + des.condition_restriction + '</span>件</div>';
                }

              }

              html += '</div>' +
                '</div>' +
                '</div>';
            }
          }


          html += '</div>' +
            '</div>';

          html += "</div>" +
            '<div class="shop-summary" style="margin-top:0.4rem">' +
            data.data.description +
            "</div>";

          $(".shop-detail").append(html);
          $("#shop-bottom-price").html(data.data.sku[0].sale_price);
          var cartGoods = cart.getCart().goods;
          for (var k = 0; k < cartGoods.length; k++) {
            if (cartGoods[k].sku_id == data.data.sku[0].id) {
              good_amount = cartGoods[k].amount;
            }
          }
          swiper();
        } else {}
      }
    );
  }

  getGoodInfo();

  function changeSpec(el, i, id, price, stock, volume) {
    index = i;
    $(el).addClass('active');
    $(el).siblings().removeClass('active');
    var val = price.toFixed(2);
    $('#shop-price').html(val);
    $('#shop-stock').html('库存：' + stock);
    $('#shop-volume').html('销量：' + volume);
    $('#shop-bottom-price').html(val);
    var i_false = true;
    var cartGoods = cart.getCart().goods;
    for (var k = 0; k < cartGoods.length; k++) {
      if (cartGoods[k].sku_id == id) {
        good_amount = cartGoods[k].amount;
        i_false = false;
      }
    }
    if (i_false) {
      good_amount = 0;
    }
  }

  function addGood() {
    var res_number = $('#restriction').html();
    good_amount = good_amount + 1;
    var good = {
      res_number: res_number,
      id: goodData.id,
      sku_id: goodData.sku[index].id,
      group_name: goodData.attr.attr_name[0].title,
      attr_name: goodData.sku[index].attr_names,
      title: goodData.title,
      pict_url: goodData.pict_url,
      price: goodData.sku[index].sale_price,
      amount: good_amount
    };



    if (res_number) {
      $.post(
        __BASEURL__ + "api/order/historyOrderCount", autoCsrf({
          shop_id: shop_id,
          goods_id: goodData.id
        }),
        function(data) {
          if (data.success) {
            res_number = res_number - data.data;
            if (good_amount > res_number) {
              if(res_number<=0){
                layer.open({
                  content: "超过限购数量",
                  skin: "msg",
                  time: 1
                });
              }else{
                layer.open({
                  content: "限购" + res_number + "件",
                  skin: "msg",
                  time: 1
                });
              }
              return false;
            }
            $.get(
              __BASEURL__ + "api/items/get_stock", {
                shop_id: shop_id,
                sku_id: goodData.sku[index].id
              },
              function(data) {
                if (data.success) {
                  sku_number = data.data.use_stock_num;
                  if (sku_number != 0) {
                    if (good_amount == 1) {
                      cart.addGood(good);
                      layer.open({
                        content: "添加成功",
                        skin: "msg",
                        time: 1
                      });
                      setTimeout(function() {
                        window.location.href = __BASEURL__ + "goods/index?shop_id=" + shop_id;
                      }, 1000);
                    } else {
                      if (good_amount <= sku_number) {
                        cart.updateGood(goodData.sku[index].id, good_amount);
                        layer.open({
                          content: "添加成功",
                          skin: "msg",
                          time: 1
                        });
                        setTimeout(function() {
                          window.location.href = __BASEURL__ + "goods/index?shop_id=" + shop_id;
                        }, 1000);
                      } else {
                        layer.open({
                          content: "库存不足",
                          skin: "msg",
                          time: 2
                        });
                      }
                    }
                  } else {
                    layer.open({
                      content: "库存不足",
                      skin: "msg",
                      time: 2
                    });
                  }
                }
              }
            );
          }
        }
      );
    } else {
      $.get(
        __BASEURL__ + "api/items/get_stock", {
          shop_id: shop_id,
          sku_id: goodData.sku[index].id
        },
        function(data) {
          if (data.success) {
            sku_number = data.data.use_stock_num;
            if (sku_number != 0) {
              if (good_amount == 1) {
                cart.addGood(good);
                layer.open({
                  content: "添加成功",
                  skin: "msg",
                  time: 1
                });
                setTimeout(function() {
                  window.location.href = __BASEURL__ + "goods/index?shop_id=" + shop_id;
                }, 1000);
              } else {
                if (good_amount <= sku_number) {
                  cart.updateGood(goodData.sku[index].id, good_amount);
                  layer.open({
                    content: "添加成功",
                    skin: "msg",
                    time: 1
                  });
                  setTimeout(function() {
                    window.location.href = __BASEURL__ + "goods/index?shop_id=" + shop_id;
                  }, 1000);
                } else {
                  layer.open({
                    content: "库存不足",
                    skin: "msg",
                    time: 2
                  });
                }
              }
            } else {
              layer.open({
                content: "库存不足",
                skin: "msg",
                time: 2
              });
            }
          }
        }
      );
    }


  }

  window.changeSpec = changeSpec;
  window.addGood = addGood;
});