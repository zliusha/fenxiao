<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>云店宝scrm</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    body {
      background-color: #f4f4f8;
    }
    .w-aside,
    .w-content {
      top: 0!important;
    }
    .w-aside {
      border-right: 1px solid #f4f4f8;
    }
    .w-aside .nav-item__active .subnav {
      display: block;
    }
    .w-aside .subnav>.subnav-item__active>a,
    .w-aside .subnav>.subnav-item__active>a:hover {
      color: #5aa2e7;
      background-color: #eceff4;
    }
    .w-aside .nav>li.nav-item__active>a .icon-arrow-down {
      -webkit-transform: rotate(180deg);
      transform: rotate(180deg);
    }
    .nav-tabs {
      margin: 0 auto;
    }
    .loading-box {
      display: block;
    }
    .error-box {
      padding: 200px 0;
      text-align: center;
    }
    .error-pic .iconfont {
      font-size: 80px;
      color: #f86868;
      line-height: 1;
    }
  </style>
</head>
<body>
  <div id="app">
    <div v-show="menus.length > 0 && !loading" class="has-aside" style="display: none;">
      <aside class="w-aside">
        <ul class="nav">
          <li v-for="menu in menus" :key="menu.id" class="nav-item" :class="{'nav-item__active': menu.open}">
            <a href="javascript:;" @click="toggleMenu(menu.id)">
              <span v-if="menu.menu_icon" class="iconfont" :class="'' + menu.menu_icon"></span>{{menu.name}}<span v-if="menu.children && menu.children.length > 0" class="iconfont icon-arrow-down"></span>
            </a>
            <ul v-if="menu.children && menu.children.length > 0" class="subnav">
              <li v-for="submenuItem in menu.children" class="subnav-item" :class="{'subnav-item__active': submenuItem.fullUrl === url || (submenu && submenu.id && submenu.id === submenuItem.id)}">
                <a v-if="submenuItem.children && submenuItem.children.length > 0" href="javascript:;" :url="submenuItem.children[0].fullUrl" @click="goUrl('' + submenuItem.children[0].fullUrl, 'submenu', submenuItem.id)">{{submenuItem.name}}</a>
                <a v-else href="javascript:;" :url="submenuItem.fullUrl" @click="goUrl('' + submenuItem.fullUrl)">{{submenuItem.name}}</a>
              </li>
            </ul>
          </li>
        </ul>
      </aside>
      <section class="w-content">
        <ul v-if="submenu && submenu.children && submenu.children.length > 0" class="nav nav-tabs">
          <li v-for="submenuItem in submenu.children" :key="submenuItem.id" role="presentation" :class="{'active': submenuItem.fullUrl === url}">
            <a href="javascript:;" :data-url="submenuItem.fullUrl" @click="goUrl('' + submenuItem.fullUrl, 'submenu', submenu.id)">{{submenuItem.name}}</a>
          </li>
        </ul>
        <iframe :src="url" frameborder="0" style="width: 100%;height: 100%;border: 0;"></iframe>
      </section>
    </div>
    <div v-show="loading" class="loading-box" style="display: none;">
      <div class="loading">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
      </div>
    </div>
    <div v-show="error" class="error-box" style="display: none;">
      <p class="error-pic"><span class="iconfont icon-danger"></span></p>
      <h3 class="error-title">{{error}}</h3>
      <p class="error-button mt20">
        <button type="button" class="btn btn-large btn-primary" @click="getMenus">重新加载</button>
      </p>
    </div>
  </div>
  <?=static_original_url('libs/jquery/3.2.1/jquery.min.js');?>
  <?=static_original_url('libs/vue/2.0/vue.min.js');?>
  <script>
    var app = new Vue({
      el: '#app',
      data: {
        loading: true,
        error: '',
        url: '',
        menus: [],
        submenu: {
          id: '',
          children: []
        }
      },
      created: function() {
        this.getMenus()
      },
      methods: {
        getMenus: function () {
          var _this = this;
          
          _this.loading = true;
          _this.error = '';

          $.get(__BASEURL__ + 'scrm_api/get_menu', function (res) {
            _this.loading = false;
            
            if (res.success) {
              var menus = res.data.menus.data;

              for (var i = 0, l = menus.length; i < l; i++) {
                menus[i].open = false;
              }

              _this.menus = menus;
              _this.setDefaultUrl();
            } else {
              _this.error = res.msg
            }
          })
        },
        setDefaultUrl: function () {
          var lastMenu = this.menus[this.menus.length - 1];

          // 默认是公众号页面
          lastMenu.open = true;
          this.url = lastMenu.children[1].fullUrl;
        },
        toggleMenu: function(id) {
          var menus = this.menus;

          for(var i = 0, l = menus.length; i < l; i++) {
            if (menus[i].id === id) {
              menus[i].open = !menus[i].open;
            } else {
              menus[i].open = false;
            }
          }

          this.menus = menus;
        },
        goUrl(url, type, id) {
          var menus = this.menus;
          var submenu = {
            id: '',
            children: []
          };

          if (!url) {
            return;
          }

          if (type && type === 'submenu') {
            menus.forEach(function(menu) {
              menu.children.forEach(function(submenuItem){
                if (submenuItem.id === id) {
                  submenu = submenuItem;
                }
              })
            })
          }

          this.submenu = submenu;
          this.url = url;
        }
      }
    });
    
    // 接受内嵌页面的消息
    window.addEventListener('message', function(e){
      var msg = e.data;
      var origin = e.origin;

      if (msg === 'refresh' && (origin === 'https://appscrmdev.ecbao.cn' || origin === 'https://appscrm.ecbao.cn')) {
        window.location.reload();
      }
    })
  </script>
</body>
</html>
