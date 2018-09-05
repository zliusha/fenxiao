/**
 * areaselect.js
 * by liangya
 * date: 2017-08-14
 * @param {[object]} options 
 *        options.areaList 省市区列表
 *        options.disabledList 禁选项列表
 *        options.checkedList 已选项列表
 */
function Area(options) {
  var $js_province_list = $('.js_province_list'),
    $js_citys_box = $('#js_citys_box'),
    _this = this;

  _this.areaList = (options && options.areaList) ? options.areaList : [];
  _this.disabledList = (options && options.disabledList) ? options.disabledList : [];
  _this.checkedList = (options && options.checkedList) ? options.checkedList : [];

  // 获取所有省市区
  function getAreaList() {
    if (_this.areaList.length > 0) {
      var provinces = _this.areaList.filter(function (item) {
        return item.level == '1';
      });

      fillProvince(provinces);
    } else {
      $.get(__BASEURL__ + 'area_api/get_all', function (data) {
        if (data.success) {
          _this.areaList = data.data.items;

          var provinces = _this.areaList.filter(function (item) {
            return item.level == '1';
          });

          fillProvince(provinces);
        }
      });
    }
  }

  getAreaList();

  // 填充省份
  function fillProvince(provinces) {
    $js_province_list.html('');
    $js_citys_box.html('');

    $.each(provinces, function (i) {
      $js_province_list.append('<option value="' + provinces[i].id + '">' + provinces[i].name + '</option>');

      var citys = _this.areaList.filter(function (item) {
        return item.pid == provinces[i].id;
      });

      fillCity(provinces[i].id, citys);
    });

    // 设置不可选择的省市
    setDisabled();

    // 设置已选的省市
    setChecked();

    // 默认选中第一个省份
    $js_province_list.find('option').eq(0).prop('selected', true);
    $js_citys_box.find('.js_citys_list').eq(0).removeClass('hide');
  }

  // 填充城市
  function fillCity(province, citys) {
    var city_item = '<li>' +
      '<label class="checkbox-inline">' +
      '<span class="u-checkbox">' +
      '<input class="js_citys_checkall" type="checkbox" value="' + province + '">' +
      '<span class="checkbox-icon"></span>' +
      '</span>全选' +
      '</label>' +
      '</li>';

    $.each(citys, function (i) {
      city_item += '<li>' +
        '<label class="checkbox-inline">' +
        '<span class="u-checkbox">' +
        '<input class="js_citys_checkitem" type="checkbox" data-id="' + citys[i].id + '" data-name="' + citys[i].name + '">' +
        '<span class="checkbox-icon"></span>' +
        '</span>' + citys[i].name +
        '</label>' +
        '</li>';
    });

    $js_citys_box.append('<ul id="city_' + province + '" class="region-list js_citys_list hide">' + city_item + '</ul>');
  }

  // 选择省份
  $js_province_list.on('change', function () {
    var province = $(this).val();

    $('.js_citys_list').addClass('hide');
    $('#city_' + province).removeClass('hide');
  });

  // 选择全部城市
  $js_citys_box.on('click', '.js_citys_checkall', function () {
    var checked = $(this).prop('checked');
    var $js_citys_list = $(this).parents('.js_citys_list');

    $js_citys_list.find('.js_citys_checkitem').each(function () {
      var disabled = $(this).prop('disabled');

      // 判断是否为禁止勾选项
      if (disabled) {
        $(this).prop('checked', false);
      } else {
        $(this).prop('checked', checked);
      }
    });

    getSelectedArea();
  });

  // 选择单个城市
  $js_citys_box.on('click', '.js_citys_checkitem', function () {
    var $js_citys_list = $(this).parents('.js_citys_list');
    var citys_length = $js_citys_list.find('.js_citys_checkitem').length;
    var selected_citys_length = $js_citys_list.find('.js_citys_checkitem:checked').length;

    // 判断是否全部选中省
    if (citys_length !== selected_citys_length) {
      $js_citys_list.find('.js_citys_checkall').prop('checked', false);
    } else {
      $js_citys_list.find('.js_citys_checkall').prop('checked', true);
    }

    getSelectedArea();
  });

  // 获取选中的省市
  function getSelectedArea() {
    var selectedArea = [],
      selectedRegion = [];

    $('.js_citys_checkall').each(function (i) {
      var $js_citys_list = $(this).parents('.js_citys_list'),
        $citys = $js_citys_list.find('.js_citys_checkitem'),
        citys_length = $citys.length,
        $selected_citys = $js_citys_list.find('.js_citys_checkitem:checked'),
        selected_citys_length = $selected_citys.length,
        province_code = $(this).val(),
        province_name = $js_province_list.find('[value=' + province_code + ']').text();

      if (citys_length == selected_citys_length) {
        // 全部选中省
        selectedArea.push(province_code);
        selectedRegion.push(province_name);
      } else if (selected_citys_length > 0) {
        var selectedCity = [];
        // 部分选中省
        $.each($selected_citys, function (i) {
          var city_code = $(this).attr('data-id'),
            city_name = $(this).attr('data-name');

          selectedArea.push(city_code);
          selectedCity.push(city_name);
        });

        selectedRegion.push(province_name + '[' + selectedCity.join('、') + ']');
      }
    });

    return {
      selectedArea: selectedArea,
      selectedRegion: selectedRegion
    }
  }

  // 设置禁选项
  function setDisabled() {
    $.each(_this.disabledList, function (i) {
      if (_this.disabledList[i] % 1e4 == 0) {
        // 当前为省
        var province = $('.js_citys_checkall[value="' + _this.disabledList[i] + '"]');

        province.prop('disabled', true);
        // 设置省以下的城市
        province.parents('.js_citys_list').find('.js_citys_checkitem').prop('disabled', true);
      } else if (_this.disabledList[i] % 1e2 == 0) {
        // 当前为市
        var city = $('.js_citys_checkitem[data-id="' + _this.disabledList[i] + '"]');

        city.prop('disabled', true);
      } else {
        // 当前为区
        console.log('地区');
      }
    });
  }

  // 设置已选项
  function setChecked() {
    $.each(_this.checkedList, function (i) {
      if (_this.checkedList[i] % 1e4 == 0) {
        // 当前为省
        var province = $('.js_citys_checkall[value="' + _this.checkedList[i] + '"]');

        if (!province.prop('disabled')) {
          province.prop('checked', true);
          // 设置省以下的城市
          province.parents('.js_citys_list').find('.js_citys_checkitem').each(function () {
            if (!$(this).prop('disabled')) {
              $(this).prop('checked', true);
            }
          });
        }
      } else if (_this.checkedList[i] % 1e2 == 0) {
        // 当前为市
        var city = $('.js_citys_checkitem[data-id="' + _this.checkedList[i] + '"]');

        if (!city.prop('disabled')) {
          city.prop('checked', true);
        }
      } else {
        // 当前为区
        console.log('地区');
      }
    });
  }

  return {
    getSelectedArea: getSelectedArea
  }
}