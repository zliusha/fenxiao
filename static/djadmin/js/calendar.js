/**
 * 完整代码
 */

;(function ($, window, document, undefined) {
  var Calendar = function (element, options) {
    this.$calendar = element;
    this.defaults = {
      ifSwitch: true,
      backToday: false
    };
    this.options = $.extend({}, this.defaults, options);
  };

  Calendar.prototype = {
    showCalendar: function () { // 输入数据并显示
      var self = this;
      var year = dateObj.getDate().getFullYear();
      var month = dateObj.getDate().getMonth() + 1;
      var dateStr = returnDateStr(dateObj.getDate());
      var firstDay = new Date(year, month - 1, 1); // 当前月的第一天

      this.$calendarTitle_text.text(year + '年' + dateStr.substr(4, 2) + '月');

      this.$calendarDate_item.each(function (i) {
        // allDay: 得到当前列表显示的所有天数
        var allDay = new Date(year, month - 1, i + 1 - firstDay.getDay());
        var allDay_str = returnDateStr(allDay);

        $(this).text(allDay.getDate()).attr('data', allDay_str);

        if (returnDateStr(new Date()) === allDay_str) {
          $(this).attr('class', 'item item-curDay');
        } else if (returnDateStr(firstDay).substr(0, 6) === allDay_str.substr(0, 6)) {
          $(this).attr('class', 'item item-curMonth');
        } else {
          $(this).attr('class', 'item');
        }
      });

      // 已选择的情况下，切换日期也不会改变
      if (self.selected_data) {
        var selected_elem = self.$calendar_date.find('[data='+self.selected_data+']');

        selected_elem.addClass('item-selected');
      }
    },

    renderDOM: function () { // 渲染DOM
      this.$calendar_title = $('<div class="calendar-title"></div>');
      this.$calendar_week = $('<ul class="calendar-week"></ul>');
      this.$calendar_date = $('<ul class="calendar-date"></ul>');
      this.$calendar_today = $('<div class="calendar-today"></div>');


      var _titleStr = '<a href="javascript:;" class="arrow-prev">&lt;&lt;</a>'+
                      '<span class="title"></span>'+
                      '<a href="javascript:;" class="arrow-next">&gt;&gt;</a>';

      var _weekStr =  '<li><span class="item">日</span></li>'+
                      '<li><span class="item">一</span></li>'+
                      '<li><span class="item">二</span></li>'+
                      '<li><span class="item">三</span></li>'+
                      '<li><span class="item">四</span></li>'+
                      '<li><span class="item">五</span></li>'+
                      '<li><span class="item">六</span></li>';
      var _dateStr = '';
      var _dayStr = '<i class="triangle"></i>'+
                    '<p class="date"></p>'+
                    '<p class="week"></p>';

      for (var i = 0; i < 6; i++) {
        _dateStr += '<li><span class="item"></span></li>'+
                    '<li><span class="item"></span></li>'+
                    '<li><span class="item"></span></li>'+
                    '<li><span class="item"></span></li>'+
                    '<li><span class="item"></span></li>'+
                    '<li><span class="item"></span></li>'+
                    '<li><span class="item"></span></li>';
      }

      this.$calendar_title.html(_titleStr);
      this.$calendar_week.html(_weekStr);
      this.$calendar_date.html(_dateStr);
      this.$calendar_today.html(_dayStr);

      this.$calendar.append(this.$calendar_title, this.$calendar_week, this.$calendar_date, this.$calendar_today);
      this.$calendar.show();
    },

    inital: function () { // 初始化
      var self = this;

      this.renderDOM();

      this.$calendarTitle_text = this.$calendar_title.find('.title');
      this.$backToday = $('#backToday');
      this.$arrow_prev = this.$calendar_title.find('.arrow-prev');
      this.$arrow_next = this.$calendar_title.find('.arrow-next');
      this.$calendarDate_item = this.$calendar_date.find('.item');
      this.$calendarToday_date = this.$calendar_today.find('.date');
      this.$calendarToday_week = this.$calendar_today.find('.week');

      this.selected_data = 0;

      this.showCalendar();

      if (this.options.ifSwitch) {
        this.$arrow_prev.bind('click', function () {
          var _date = dateObj.getDate();

          dateObj.setDate(new Date(_date.getFullYear(), _date.getMonth() - 1, 1));

          self.showCalendar();
        });

        this.$arrow_next.bind('click', function () {
          var _date = dateObj.getDate();

          dateObj.setDate(new Date(_date.getFullYear(), _date.getMonth() + 1, 1));

          self.showCalendar();
        });
      }

      if (this.options.backToday) {
        var cur_month = dateObj.getDate().getMonth() + 1;

        this.$backToday.bind('click', function () {
          var item_month = $('.item-curMonth').eq(0).attr('data').substr(4, 2);
          var if_lastDay = (item_month != cur_month) ? true : false;

          if (!self.$calendarDate_item.hasClass('item-curDay') || if_lastDay) {
            dateObj.setDate(new Date());

            self.showCalendar();
          }
        });
      }

      this.$calendarDate_item.click(function () {
        var _dateStr = $(this).attr('data');
        var _date = changingStr(addMark(_dateStr));
        var $curClick = null;

        self.selected_data = $(this).attr('data');

        dateObj.setDate(new Date(_date.getFullYear(), _date.getMonth(), 1));

        if (!$(this).hasClass('item-curMonth')) {
          self.showCalendar();
        }

        $curClick = self.$calendar_date.find('[data='+_dateStr+']');
        $curDay = self.$calendar_date.find('.item-curDay');
        if (!$curClick.hasClass('item-selected')) {
          self.$calendarDate_item.removeClass('item-selected');

          $curClick.addClass('item-selected');
        }
      });
    },
    constructor: Calendar
  };

  $.fn.calendar = function (options) {
    var calendar = new Calendar(this, options);

    return calendar.inital();
  };


  // ========== 使用到的方法 ==========

  var dateObj = (function () {
    var _date = new Date();

    return {
      getDate: function () {
        return _date;
      },

      setDate: function (date) {
        _date = date;
      }
    }
  })();

  function returnDateStr(date) { // 日期转字符串
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();

    month = month <= 9 ? ('0' + month) : ('' + month);
    day = day <= 9 ? ('0' + day) : ('' + day);

    return year + month + day;
  };

  function changingStr(fDate) { // 字符串转日期
    var fullDate = fDate.split("-");
    
    return new Date(fullDate[0], fullDate[1] - 1, fullDate[2]); 
  };

  function addMark(dateStr) { // 给传进来的日期字符串加-
    return dateStr.substr(0, 4) + '-' + dateStr.substr(4, 2) + '-' + dateStr.substring(6);
  };

  function isLeapYear(year) { // 判断闰年
    return (year % 4 == 0) && (year % 100 != 0 || year % 400 == 0);
  }

})(jQuery, window, document);