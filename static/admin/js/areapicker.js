/**
 * jquery.areapicker.js
 * version: 1.0.0
 * by liangya
 */
(function (factory) {
  if (typeof define === "function" && (define.amd || define.cmd) && !jQuery) {
    // AMD或CMD
    define(["jquery"], factory);
  } else if (typeof module === "object" && module.exports) {
    // Node/CommonJS
    module.exports = function (root, jQuery) {
      if (jQuery === undefined) {
        if (typeof window !== "undefined") {
          jQuery = require("jquery");
        } else {
          jQuery = require("jquery")(root);
        }
      }
      factory(jQuery);
      return jQuery;
    };
  } else {
    //Browser globals
    factory(jQuery);
  }
})(function ($) {
  $.support.cors = true;

  $.fn.areapicker = function (params) {
    var defaults = {
      dataUrl: __BASEURL__ + "area_api/get_all", //数据库地址
      dataType: "json",
      provinceField: "province", //省份字段名
      cityField: "city", //城市字段名
      areaField: "area", //地区字段名
      required: false,
      province: 0, //省份编码
      city: 0, //城市编码
      area: 0 //地区编码
    };

    var options = $.extend({}, defaults, params);

    return this.each(function () {
      var $this = $(this);

      var $province = $this.find(
          'select[name="' + options.provinceField + '"]'
        ),
        $city = $this.find('select[name="' + options.cityField + '"]'),
        $area = $this.find('select[name="' + options.areaField + '"]');

      $.ajax({
        url: options.dataUrl,
        type: "GET",
        dataType: options.dataType,
        success: function (data) {
          if (data.success) {
            data = data.data.items;

            var province, city, area;

            var updateData = function () {
              (province = []), (city = []), (area = []);

              province = data.filter(function (item) {
                return item.level == "1";
              });

              city = data.filter(function (item) {
                return item.pid == options.province && item.level == "2";
              });

              area = data.filter(function (item) {
                return item.pid == options.city && item.level == "3";
              });
            };

            var format = {
              province: function () {
                $province.empty();

                if (!options.required) {
                  $province.append(
                    '<option data-code="" data-name="" value="">请选择</option>'
                  );
                }

                $.each(province, function (i, e) {
                  $province.append(
                    '<option data-code="' +
                    province[i].id +
                    '" data-name="' +
                    province[i].name +
                    '" value="' +
                    province[i].id +
                    '">' +
                    province[i].name +
                    "</option>"
                  );
                });

                if (options.province) {
                  var value = options.province;
                  $province.val(value);
                }
                this.city();
              },
              city: function () {
                $city.empty();

                if (!options.required) {
                  $city.append(
                    '<option data-code="" data-name="" value="">请选择</option>'
                  );
                }

                $.each(city, function (i, e) {
                  $city.append(
                    '<option data-code="' +
                    city[i].id +
                    '" data-name="' +
                    city[i].name +
                    '" value="' +
                    city[i].id +
                    '">' +
                    city[i].name +
                    "</option>"
                  );
                });

                if (options.city) {
                  var value = options.city;
                  $city.val(value);
                }

                this.area();
              },
              area: function () {
                $area.empty();

                if (!options.required) {
                  $area.append(
                    '<option data-code="" data-name="" value="">请选择</option>'
                  );
                }

                $.each(area, function (i, e) {
                  $area.append(
                    '<option data-code="' +
                    area[i].id +
                    '" data-name="' +
                    area[i].name +
                    '" value="' +
                    area[i].id +
                    '">' +
                    area[i].name +
                    "</option>"
                  );
                });

                if (options.area) {
                  var value = options.area;
                  $area.val(value);
                }
              }
            };

            //事件绑定
            $province.on("change", function () {
              options.province =
                $(this)
                .find("option:selected")
                .data("code") || 0;
              options.city = 0;
              options.area = 0;
              updateData();
              format.city();
            });

            $city.on("change", function () {
              options.city =
                $(this)
                .find("option:selected")
                .data("code") || 0;
              options.area = 0;
              updateData();
              format.area();
            });

            $area.on("change", function () {
              options.area =
                $(this)
                .find("option:selected")
                .data("code") || 0;
            });

            updateData();
            format.province();
          }
        }
      });
    });
  };
});