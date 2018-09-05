<?php

/**
 * @Author: binghe
 * @Date:   2018-07-05 16:38:10
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-23 10:58:42
 */
return [
    'app_id'       => 'wxf40d245aecc8f942',
    'app_secret'   => '2702feae86a7b18f432c677753d34975',
    'mch_id'       => '1483680372',
    'key'          => 'ce7a30f07af910ce06f6d7a22c713b84',

    //嘿卡价格 一店一码价格
    'hcard_money'  => 99,
    'ydym_money'   => 560,
    //小程序模板
    'template_map' => [
        'money_change'        => [
            'name'        => '余额变动提醒',
            'template_id' => '7VfEJORP72wEdoMtfHCTS_dfKxyp70Iy6xMjxxNpVZs',
            'page'        => 'pages/mine/index/index',
        ],
        'buy_success'         => [
            'name'        => '购买成功通知',
            'template_id' => 'SXpKbyl2ojlHif8fdlGp4ozbg9u-EnX33NPgVJODX1o',
            'page'        => 'pages/mine/order/detail/detail',
        ],
        'sell_success'        => [
            'name'        => '销售成功通知',
            'template_id' => 'vO1GWRHcRHKHpFsCLG9YGq9vDrvP-gI8Pm3OPunMHL0',
            'page'        => '',
        ],
        'collect_success'     => [
            'name'        => '收藏成功通知',
            'template_id' => 'mX-XFpyOrQyknH-7211ndOUz1UNXNZtKL-OHqWVUxcc',
            'page'        => '',
        ],
        'new_customer_access' => [
            'name'        => '新客访问提醒',
            'template_id' => '08mwrfAnK29-rWeng_rQeYrSyA5gYw6G5vz3I8k2UiE',
            'page'        => '',
        ],
        'friend_help_result' => [
            'name'        => '好友帮助结果通知',
            'template_id' => '6pWUW1XMJOLUksrUqa_zdFVN1JXWm8KPfWLbZsffWkM',
            'page'        => 'pages/home/jz/detail/detail',
        ],

    ]
];
