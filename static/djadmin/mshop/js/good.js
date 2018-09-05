$(function () {
  var classTpl = document.getElementById("classTpl").innerHTML,
    manyTpl = document.getElementById("manyTpl").innerHTML;

  var goods_id = $("#goods_id").val(),
    shop_id = $('#shop_id').val(),
    $goodTitle = $("#good_title"),
    $goodCode = $('#good_code'),
    $goodPrice = $("#good_price"),
    $goodMember = $('#good_member'),
    $goodDetail = $("#good_detail"),
    $goodBox = $('#good_box'),
    $goodStock = $('#good_stock'),
    $editCateModal = $('#editCateModal'),
    $editImgModal = $('#editImgModal'),
    $editConfirm = $('#edit-confirm'),
    $cateName = $('#cate_name'),
    $cateSort = $('#cate_sort'),
    $countWeight = $('#count-weight'),
    $btnConfirm = $("#btn-confirm");

  var logoTpl = document.getElementById("logoTpl").innerHTML,
    propTpl = document.getElementById("propTpl").innerHTML;

  var sku_type = 0,
  sku_id = '',
  thisNumber = '',
  many_value = [],
  cate_ids = [],
  is_open_mprice = 0,
  measure_type = 1,
  many = {
    list: []
  },
  logo = {
    list: [{
      pic: ''
    }]
  },
  prop = {
    list: []
  };

  if (!goods_id) {
    addGood();
    initLogo();
    initCate(shop_id);
  } else {
    initLogo();
    getGoodInfo();
  }

  function initLogo() {
    $("#logoTbody").html(template(logoTpl, logo));
    initUploadBanner();
    if ($("#good-form").data("bootstrapValidator")) {
      $("#good-form").data("bootstrapValidator").destroy();
      $("#good-form").data("bootstrapValidator", null);
      addGood();
    }
  }

  function initCate(shop_id) {
    $.getJSON(__BASEURL__ + "mshop/items_api/get_all_cate", {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        var cate = {
          rows: data.data
        }
        $("#classTbody").html(template(classTpl, cate));
        if (cate_ids) {
          for (var i = 0; i < cate_ids.length; i++) {
            $(".cate-label[data-value='" + cate_ids[i] + "']").addClass('active');
          }
        }
      }
    });
  }

  // 获取商品信息
  function getGoodInfo() {
    $.getJSON(
      __BASEURL__ + "mshop/items_api/goods_info", {
        goods_id: goods_id
      },
      function (data) {
        if (data.success) {

          logo.list = [];
          if (data.data.picarr) {
            var img = data.data.picarr.split(',');
            img.forEach(function (item) {
              if (item) {
                logo.list.push({
                  pic: item
                })
              }
            })
          } else {
            logo.list.push({
              pic: data.data.pict_url
            })
          }
          if (logo.list.length != 5) {
            logo.list.push({
              pic: ''
            })
          }
          initLogo();
          
          if (data.data.pro_attrs) {
            prop.list = data.data.pro_attrs;
          }
          if (prop.list.length > 0) {
            $('.prop-group').hide();
            $('#prop').show();
            $("#propTbody").html(template(propTpl, prop));
          }

          $goodTitle.val(data.data.title);
          $goodDetail.val(data.data.description);
          $goodCode.val(data.data.goods_sn);
          cate_ids = data.data.cate_ids.split(",");

          shop_id = data.data.shop_id;
          initCate(shop_id);

          // if (data.data.is_open_mprice == 0) {
          //   is_open_mprice = 0
          //   $('[name="goods_member"]').prop('checked', false)
          // } else {
          //   is_open_mprice = 1
          //   $('[name="goods_member"]').prop('checked', true)
          // }

          if (data.data.measure_type == 1) {
            measure_type = 1
            $('[name="goodMetering"][value="1"]').prop('checked', true)      
          } else {
            measure_type = 2
            $('[name="goodMetering"][value="2"]').prop('checked', true)
            $countWeight.show();
            $("#count-weight option[value='" + data.data.unit_type + "']").attr('selected', true);
            changeWeightType();
          }

          if (data.data.tag == 0) {
            $('[name="goodLabel"][value="0"]').prop("checked", true);
          } else if (data.data.tag == 1) {
            $('[name="goodLabel"][value="1"]').prop("checked", true);
          } else {
            $('[name="goodLabel"][value="2"]').prop("checked", true);
          }

          if (data.data.sku_type == 0) {
            sku_type = 0;
            $goodPrice.val(data.data.inner_price);
            $goodMember.val(data.data.sku[0].member_price)
            $goodBox.val(data.data.sku[0].box_fee);
            sku_id = data.data.sku[0].id;
            if (data.data.sku[0].use_stock_num < 0) {
              $('[name="goodStock"][value="0"]').prop("checked", true);
            } else {
              $('[name="goodStock"][value="1"]').prop("checked", true);
              $goodStock.show();
              $goodStock.val(data.data.sku[0].use_stock_num);
            }
          } else {
            sku_type = 1;
            many_value = [];
            $('#many').show();
            $('#only-type-box').hide();
            for (var i = 0; i < data.data.sku.length; i++) {
              many.list.push({
                sku_name: data.data.sku[i].attr_names,
                box: data.data.sku[i].box_fee,
                sku_code: data.data.sku[i].goods_sku_sn,
                member_price: data.data.sku[i].member_price,
                price: data.data.sku[i].sale_price,
                sku_id: data.data.sku[i].id,
                stock: data.data.sku[i].use_stock_num
              });
              if (data.data.sku[i].use_stock_num < 0) {
                many.list[i].is_stock = false
                many.list[i].stock = ''
              } else {
                many.list[i].is_stock = true
              }
            }
            $("#manyTbody").html(template(manyTpl, many));
          }
          judgeWeight();
          $('[name="good_stock"]').each(function () {
            var val = parseFloat($(this).val()) || 0;
            $(this).val(val)
          })
          judgeMember();
          addGood();
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 初始化商品图上传
  function initUploadBanner() {
    $(".upload-input").each(function (i, e) {
      $(this).on('click', function () {
        $(this).val('')
      })
      $(this).on('change', function (e) {
        var reg = /\.(jpg|png|JPG|PNG)$/;
        if (!reg.test(e.target.value)) {
          new Msg({
            type: "danger",
            msg: '请上传jpg、png格式图片'
          });
          return
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          options.imgSrc = e.target.result;
          $editImgModal.modal('show');
          $('#upload-logo').val('');
          mySlider.setValue(0);
          cropper = $('.imageBox').cropbox(options);
        }
        reader.readAsDataURL(this.files[0]);
        thisNumber = i;
      })
    });
  }

  function delLogo(el, i) {
    if (logo.list.length == 5) {
      if (logo.list[4].pic != "") {
        logo.list.splice(i, 1);
        logo.list.push({
          pic: ""
        });
      } else {
        logo.list.splice(i, 1);
      }
    } else {
      logo.list.splice(i, 1);
    }
    initLogo();
  }

  //初始化图片裁剪
  var options =
  {
      thumbBox: '.thumbBox',
      spinner: '.spinner',
      imgSrc: ''
  }
  var cropper = $('.imageBox').cropbox(options);

  var mySlider = new Slider('#ex1',{
    formatter: function(value) {
      var radio = value/20 + 1;
      cropper.zoomChange(radio);
      return 'Current value: ' + value;
    }
  });

  $('#btnCrop').on('click', function(){
      var img = cropper.getDataURL();
      var source = convertBase64UrlToBlob(img);
      if (source.size > 1024 * 1024) {
        new Msg({
          type: "danger",
          msg: '请上传小于1M的图片'
        });
        return
      }
      showBanner(source, thisNumber);
  })
  $('#btnZoomIn').on('click', function(){
      cropper.zoomIn();
  })
  $('#btnZoomOut').on('click', function(){
      cropper.zoomOut();
  })

 //上传图片
 function showBanner(source, thisNumber) {
   $.getJSON(
     __BASEURL__ + "qiniu_api/get_token", {
       type: 'wsc_goods'
     },
     function (data, up) {
       if (data.success) {
         upload_url = data.data.upload_url;
         up_token = data.data.up_token;
         var formData = new FormData();
         var key = 'wsc_goods' + '/' + new Date().getTime() + '_' + Math.floor(1000 + Math.random() * (9999 - 1000)) + '.' + 'png';
         formData.append('file', source);
         formData.append('key', key);
         formData.append('token', up_token);
         $.ajax({
           url: upload_url,
           type: 'post',
           processData: false,
           contentType: false,
           data: formData,
           dataType: 'json',
           success: function (up, file, info) {
             var res = info.responseJSON;
             var halfpath = res.key;
             var fullpath = __UPLOADURL__ + halfpath;
             logo.list[thisNumber].pic = halfpath;
             $('#good_logo0').val(halfpath).blur();
             var num = logo.list.length;
             if(num < 5) {
              if (logo.list[num - 1].pic) {
                logo.list.push({
                  pic: ""
                });
              }
             }

             initLogo();
             $editImgModal.modal('hide');
           },
           error: function (jqXHR, textStatus, errorThrown) {

           }
         });
       }
     }
   );
 }

  function convertBase64UrlToBlob(urlData){

    var bytes=window.atob(urlData.split(',')[1]);        //去掉url的头，并转换为byte

    //处理异常,将ascii码小于0的转换为大于0
    var ab = new ArrayBuffer(bytes.length);
    var ia = new Uint8Array(ab);
    for (var i = 0; i < bytes.length; i++) {
      ia[i] = bytes.charCodeAt(i);
    }

    return new Blob( [ab] , {type : 'image/png'});
  }

  function chose_get_value(select) {
    return $(select).val();
  }

  function addMany() {
    many.list.push({
      sku_name: '',
      price: '',
      box: '',
      stock: '',
      is_stock: false
    });
    $('#many').show();
    $('#only-type-box').hide();
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#manyTbody").html(template(manyTpl, many));
    judgeWeight();
    sku_type = 1;
    addGood();
    judgeMember();
  }

  function judgeMember() {
    if (is_open_mprice) {
      $('.good_member').each(function () {
        $(this).prop('disabled', false)
      })
    } else {
      $('.good_member').each(function () {
        $(this).prop('disabled', true)
      })
    }
  }

  function addManyItem() {
    many.list.push({
      sku_name: '',
      price: '',
      box: '',
      stock: '',
      is_stock: false
    });
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#manyTbody").html(template(manyTpl, many));
    judgeWeight()
    addGood();
    judgeMember();
  }

  function judgeWeight() {
    if (measure_type == 1) {
      $('.good_stock').each(function () {
        $(this).attr("name", 'good_stock');
      })
    } else {
      $('.good_stock').each(function () {
        $(this).attr("name", 'good_stock2');
      })
    } 
  }

  function delManyItem(i) {
    if (many.list.length == 1) {
      many.list.splice(i, 1);
      $('#many').hide();
      $('#only-type-box').show();
      sku_type = 0;
      $("#good-form").data("bootstrapValidator").destroy();
      $("#good-form").data("bootstrapValidator", null);
      judgeWeight()
      addGood();
    } else {
      many.list.splice(i, 1);
      $("#good-form").data("bootstrapValidator").destroy();
      $("#good-form").data("bootstrapValidator", null);
      $("#manyTbody").html(template(manyTpl, many));
      judgeWeight()
      addGood();
      judgeMember();
    }
  }

  function addProp() {
    if (prop.list.length > 4) {
      new Msg({
        type: 'danger',
        msg: '商品属性最多5条'
      });
      return
    }
    prop.list.push({
      name: '',
      value: ['', '']
    });
    $('#prop').show();
    $('.prop-group').hide();
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#propTbody").html(template(propTpl, prop));
    addGood();
  }

  function addPropItem() {
    prop.list.push({
      name: '',
      value: ['', '']
    });
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#propTbody").html(template(propTpl, prop));
    addGood();
  }

  function addPropValue(i) {
    if (prop.list[i].value.length > 4) {
      new Msg({
        type: 'danger',
        msg: '商品属性内容最多5条'
      });
      return
    }
    prop.list[i].value.push('');
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#propTbody").html(template(propTpl, prop));
    addGood();
  }

  function delPropValue(i) {
    if (prop.list[i].value.length < 3) {
      new Msg({
        type: 'danger',
        msg: '商品属性内容最少2条'
      });
      return
    }
    prop.list[i].value.pop();
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#propTbody").html(template(propTpl, prop));
    addGood();
  }

  function delPropItem(i) {
    if (prop.list.length == 1) {
      prop.list.splice(i, 1);
      $('#prop').hide();
      $('.prop-group').show();
    } else {
      prop.list.splice(i, 1);
    }
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    $("#propTbody").html(template(propTpl, prop));
    addGood();
  }

  function addGood() {
    // 提交信息
    $("#good-form")
      .bootstrapValidator({
        fields: {
          good_title: {
            validators: {
              notEmpty: {
                message: "商品标题不能为空"
              },
              stringLength: {
                max: 60,
                message: "商品标题不得超过60个字符"
              }
            }
          },
          cate_ids: {
            validators: {
              notEmpty: {
                message: "选择商品分类"
              }
            }
          },
          good_logo0: {
            validators: {
              notEmpty: {
                message: "请上传商品logo"
              }
            }
          },
          good_price: {
            validators: {
              notEmpty: {
                message: "商品价格不能为空"
              },
              regexp: {
                regexp: /^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/,
                message: "请输入正数"
              }
            }
          },
          good_member: {
            validators: {
              notEmpty: {
                message: "会员价不能为空"
              },
              regexp: {
                regexp: /^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/,
                message: "请输入正数"
              }
            }
          },
          sku_name: {
            validators: {
              notEmpty: {
                message: "规格名称不能为空"
              }
            }
          },
          good_box: {
            validators: {
              regexp: {
                regexp: /^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/,
                message: "请输入正数"
              }
            }
          },
          good_stock: {
            validators: {
              notEmpty: {
                message: "商品库存不能为空"
              },
              regexp: {
                regexp: /^[+]{0,1}(\d+)$/,
                message: "请输入正整数"
              }
            }
          },
          good_stock2: {
            validators: {
              notEmpty: {
                message: "商品库存不能为空"
              },
              regexp: {
                regexp: /^[+]{0,1}(\d+)$|^[+]{0,1}(\d+\.\d+)$/,
                message: "请输入正数"
              }
            }
          },
          good_detail: {
            validators: {
              stringLength: {
                max: 80,
                message: "商品标题不得超过80个字符"
              }
            }
          },
          prop_name: {
            validators: {
              notEmpty: {
                message: "属性名称不能为空"
              }
            }
          },
          prop_value: {
            validators: {
              notEmpty: {
                message: "属性内容不能为空"
              }
            }
          }
        }
      })
      .on("success.form.bv", function (e) {
        // 阻止表单默认提交
        e.preventDefault();
        var good_title = $goodTitle.val(),
          picarr = [],
          tag = $('[name="goodLabel"]:checked').val(),
          unit_type = 0,
          good_code = $goodCode.val(),
          good_price = $goodPrice.val(),
          good_detail = $goodDetail.val(),
          good_box = $goodBox.val(),
          good_stock = $goodStock.val(),
          is_same = true,
          post_data;

        if (good_box == '') {
          good_box = 0;
        }
        if ($('[name="goodStock"]:checked').val() == 0) {
          good_stock = -1
        }
        var sku1 = [{
          sku_attr: [{
            attr: "商品规格",
            value: ""
          }],
          goods_sku_sn: '',
          box_fee: good_box,
          sale_price: good_price,
          use_stock_num: good_stock,
          sku_id: sku_id
        }];
        var sku2 = [];
        for (var i = 0; i < many.list.length; i++) {
          var val = many.list[i].sku_name;
          many_value.push(many.list[i].sku_name);
          if (many.list[i].box == '') {
            many.list[i].box = 0;
          }
          if (!many.list[i].is_stock) {
            many.list[i].stock = -1
          }
          sku2.push({
            sku_attr: [{
              attr: '商品规格',
              value: val
            }],
            goods_sku_sn: many.list[i].sku_code,
            box_fee: many.list[i].box,
            sale_price: many.list[i].price,
            use_stock_num: many.list[i].stock,
            sku_id: many.list[i].sku_id
          });
        }

        var attr1 = [{
          attr_name: "商品规格",
          value: [""]
        }];
        var attr2 = [{
          attr_name: '商品规格',
          value: many_value
        }];

        cate_ids = [];
        $('.cate-label.active').each(function () {
          cate_ids.push($(this).attr('data-value'))
        })

        if (cate_ids.length == 0) {
          new Msg({
            type: "danger",
            msg: "请选择商品分类"
          });
          return false;
        }

        for(var i=0;i<prop.list.length;i++) {
          var item = prop.list[i].value;
          for(var a=0;a<item.length;a++) {
            for(var b = a+1;b<item.length;b++) {
              if(item[a] == item[b]) {
                new Msg({
                  type: "danger",
                  msg: "一个属性名下属性内容不能相同"
                });
                is_same = false
              }
            }
          }
          for(var j = i+1;j<prop.list.length;j++) {
            if(prop.list[i].name == prop.list[j].name) {
              new Msg({
                type: "danger",
                msg: "属性名不能相同"
              });
              is_same = false
            }
          }
        }

        if(!is_same) {
          return false
        }

        cate_ids = cate_ids.toString();

        logo.list.forEach(function (item) {
          if (item.pic) {
            picarr.push(item.pic)
          }
        })

        if (measure_type == 1) {
          unit_type = 0
        } else {
          unit_type = $('#count-weight option:selected').val();
        }

        if (sku_type == 0) {
          post_data = {
            shop_id: shop_id,
            goods_id: goods_id,
            title: good_title,
            picarr: picarr.join(","),
            sku_type: sku_type,
            tag: tag,
            description: good_detail,
            attr: JSON.stringify(attr1),
            sku: JSON.stringify(sku1),
            cate_ids: cate_ids,
            // is_open_mprice: is_open_mprice,
            goods_sn: good_code,
            measure_type: measure_type,
            unit_type: unit_type,
            pro_attrs: JSON.stringify(prop.list)
          }
        } else {
          post_data = {
            shop_id: shop_id,
            goods_id: goods_id,
            title: good_title,
            picarr: picarr.join(","),
            sku_type: sku_type,
            tag: tag,
            description: good_detail,
            attr: JSON.stringify(attr2),
            sku: JSON.stringify(sku2),
            cate_ids: cate_ids,
            // is_open_mprice: is_open_mprice,
            goods_sn: good_code,
            measure_type: measure_type,
            unit_type: unit_type,
            pro_attrs: JSON.stringify(prop.list)
          }
        }

        // 判断是添加或编辑
        if (!goods_id) {
          post_url = __BASEURL__ + "mshop/items_api/goods_add";
        } else {
          post_url = __BASEURL__ + "mshop/items_api/goods_edit";
        }

        $btnConfirm.prop("disabled", true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
            });

            window.location.href = __BASEURL__ + "mshop/items/index";
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });

            $btnConfirm.prop("disabled", false);
          }
        });
      });
  }

  function changeSku(el, i) {
    var val = $(el).val();
    many.list[i].sku_name = val;
  }

  function changeSkuCode(el, i) {
    var val = $(el).val();
    many.list[i].sku_code = val;
  }

  function changePrice(el, i) {
    var val = $(el).val();
    many.list[i].price = val;
  }

  function changeMemberPrice(el, i) {
    var val = $(el).val();
    many.list[i].member_price = val;
  }

  function changeBox(el, i) {
    var val = $(el).val();
    many.list[i].box = val;
  }

  function changeStock(el, i) {
    var val = $(el).val();
    many.list[i].stock = val;
  }

  function changeProp(el, i) {
    var val = $(el).val();
    prop.list[i].name = val;
  }

  function changePropValue(el, i ,j) {
    var val = $(el).val();
    prop.list[i].value[j] = val;
  }

  function changeCate(el) {
    if ($(el).hasClass('active')) {
      $(el).removeClass('active')
    } else {
      $(el).addClass('active')
    }
  }

  function changeMember(el) {
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    addGood();
    if ($(el).is(":checked")) {
      is_member = true
      $('.good_member').each(function () {
        $(this).prop('disabled', false)
      })
    } else {
      is_member = false
      $('.good_member').each(function () {
        $(this).prop('disabled', true)
      })
    }
  }

  function changeMetering(el) {
    var val = $(el).val();
    if (val === '2') {
      measure_type = 2;
      $countWeight.show();
      $('.input-group-weight').each(function() {
        $(this).html('元/kg')
      })
      $('.good_stock').each(function () {
        $(this).attr("name", 'good_stock2');
      })
    } else {
      measure_type = 1;
      $countWeight.hide();
      $('.input-group-weight').each(function () {
        $(this).html('元')
      })
      $('.good_stock').each(function () {
        $(this).attr("name", 'good_stock');
      })
    }
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    addGood();
  }

  function changeStockType(el, i) {
    var val = $(el).val();
    if (val === '1') {
      $(el).parent().parent().parent().find('.good_stock').show();
      if (i || i == 0) {
        many.list[i].is_stock = true
      }
    } else {
      $(el).parent().parent().parent().find('.good_stock').hide();
      if (i || i == 0) {
        many.list[i].is_stock = false
      }
    }
    $("#good-form").data("bootstrapValidator").destroy();
    $("#good-form").data("bootstrapValidator", null);
    addGood();
  }

  function changeWeightType(el) {
    var val = $('#count-weight').find("option:selected").val();
    if (val === '1') {
      $('.input-group-weight').each(function () {
        $(this).html('元/kg')
      })
    } else if (val === '2') {
      $('.input-group-weight').each(function () {
        $(this).html('元/g')
      })
    } else if (val === '3') {
      $('.input-group-weight').each(function () {
        $(this).html('元/千克')
      })
    } else if (val === '4') {
      $('.input-group-weight').each(function () {
        $(this).html('元/克')
      })
    } else if (val === '5') {
      $('.input-group-weight').each(function () {
        $(this).html('元/斤')
      })
    } else {
      $('.input-group-weight').each(function () {
        $(this).html('元/两')
      })
    }
   }

// 验证分类表单
  function validatorCateForm() {
    $('#cate-form')
      .bootstrapValidator({
        fields: {
          cate_name: {
            validators: {
              notEmpty: {
                message: '分类名称不能为空'
              },
              stringLength: {
                max: 30,
                message: '分类名称不得超过30个字符'
              }
            }
          },
          cate_sort: {
            validators: {
              notEmpty: {
                message: '分类排序不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var cate_name = $cateName.val(),
          cate_sort = $cateSort.val(),
          cate_id = $editConfirm.data('id'),
          post_url,
          post_data;

        // 提交数据
        post_data = {
          shop_id: shop_id,
          cate_name: cate_name,
          sort: cate_sort
        }
        // 判断是添加或编辑
        if (!cate_id) {
          post_url = __BASEURL__ + 'mshop/items_api/cate_add';
        } else {
          post_data.id = cate_id;
          post_url = __BASEURL__ + 'mshop/items_api/cate_edit';
        }

        $editConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });
            initCate(shop_id);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $editConfirm.prop('disabled', false);
          $editCateModal.modal('hide');
        });
      });
  }

  validatorCateForm();

  // 显示分类弹窗
  function showCateModal() {
    $editCateModal.modal('show');
    $("#cate-form").data('bootstrapValidator').destroy();
    $('#cate-form').data('bootstrapValidator', null);
    validatorCateForm();
  }

  // 添加分类
  function addCate() {
    showCateModal();
    $editCateModal.find('.modal-title').text('添加分类');
    $cateName.val('');
    $cateSort.val('');
    $editConfirm.data('id', '');
  }


  window.addCate = addCate;
  window.addMany = addMany;
  window.addManyItem = addManyItem;
  window.delManyItem = delManyItem;
  window.addProp = addProp;
  window.addPropItem = addPropItem;
  window.addPropValue = addPropValue;
  window.delPropValue = delPropValue;
  window.delPropItem = delPropItem;
  window.changeSku = changeSku;
  window.changeSkuCode = changeSkuCode;
  window.changeProp = changeProp;
  window.changeCate = changeCate;
  window.changePropValue = changePropValue;
  window.changeMetering = changeMetering;
  window.changeMember = changeMember;
  window.changeWeightType = changeWeightType;
  window.changeStockType = changeStockType;
  window.changePrice = changePrice;
  window.changeMemberPrice = changeMemberPrice;
  window.changeBox = changeBox;
  window.changeStock = changeStock;
  window.delLogo = delLogo;
})