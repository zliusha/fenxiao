/**
 * index.js
 * by liangya
 * date: 2017-08-03
 */
$(function () {
  var $mainFrame = $('#main-frame'),
  $loadingBox = $('#loading-box');

  $('.J_NAV_ITEM a').on('click', function (e) {
    var $this = $(this),
      target = $this.attr('href');

    e.preventDefault();

    if (!target) {
      return false;
    }

    $('.J_NAV_ITEM').removeClass('active');
    $this.parent().addClass('active');

    if($loadingBox.is(':hidden')){
      $loadingBox.stop().fadeIn();
    }

    $mainFrame.attr('src', target);
  });

  $mainFrame.on('load', function () {
    $loadingBox.hide();
  });

  $loadingBox.stop().fadeIn();

  $('.J_TOGGLE_SUBNAV').on('click', function () {
    var $this = $(this);

    $this.next().slideToggle();
    $this.parent().toggleClass('open');
  });
});
