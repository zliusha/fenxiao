$(function(){
  var cart = new Cart(),
    $cartList = $('.cart-list'),
    cartTpl = document.getElementById('cartTpl').innerHTML;

  var shop_id = $("#shop-id").val();  

  function initCart(){
    var cartList = cart.getCart();
    console.info(cartList)
    $('#cartTbody').html(template(cartTpl, cartList));
    $('.shop-price').html(cart.getCart().total_price);
  }

  initCart();

  //增加
  function add(el,sku_id,i,res_number,sku_number,id){
    var count = i + 1;

    var sku_number = sku_number;  
    if (count <= sku_number) {
      if(res_number){
        $.post(
          __BASEURL__ + "api/order/historyOrderCount", autoCsrf({
            shop_id: shop_id,
            goods_id: id
          }),
          function(data) {
            if (data.success) {
              res_number = res_number - data.data;
              if(count>res_number){
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
              cart.updateGood(sku_id, count);
              initCart();
            }
          }
        );
      }else{
        cart.updateGood(sku_id, count);
        initCart();
      }
    } else {
      layer.open({
        content: "库存不足",
        skin: "msg",
        time: 2
      });
    }
    cartNumber();
  }

  //减少
  function decrease(el,sku_id,i){
    var count = i - 1;

    if(count == 0){
      cart.delGood(sku_id);
    }else{
      cart.updateGood(sku_id, count);
    }

    initCart();
    cartNumber()
  }

  //清空购物车
  function empty(){
    cart.clearCart();
    initCart();
    cartNumber()
  }

  function cartNumber(){
    var number = $('.cart-item').length;
    if(number==0){
      $('.shop-bottom').removeClass('active');
      $('.m-empty').show();
    }else{
      $('.shop-bottom').addClass('active');
      $('.m-empty').hide();
    }
  }

  cartNumber();

  window.empty = empty;
  window.add = add;
  window.decrease = decrease;
});