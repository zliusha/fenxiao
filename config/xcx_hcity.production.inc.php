<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/8
 * Time: 16:00
 */
return [
    'app_id'=>'wxadb5f8c818c977d0',
    'app_secret'=>'0cbb7a7a67268658cca3328948008c95',
    'mch_id'=>'1420965702',
    'key'=>'abvdwaAdvS923KKs2898JLKekl3a1wsc',

    //嘿卡价格 一店一码价格
    'hcard_money' => 99,
    'ydym_money' => 560,
    //小程序模板
    'template_map' => [
        'money_change'        => [
            'name'        => '余额变动提醒',
            'template_id' => 'XrlxopaoIUqO0DT8bduLQsYSE4XZMGkjGCv79afW9DU',
            'page'        => 'pages/mine/index/index',
        ],
        'buy_success'         => [
            'name'        => '购买成功通知',
            'template_id' => 'Mts6_JxNLrY0T4s_tI1pbv92bRhSt4xlRI9f-3tyjMM',
            'page'        => 'pages/mine/order/detail/detail',
        ],
        'sell_success'        => [
            'name'        => '销售成功通知',
            'template_id' => 'meu-2oeYtH1KKoI9i4_sKIldG3AI5o2oashcX62XFls',
            'page'        => '',
        ],
        'collect_success'     => [
            'name'        => '收藏成功通知',
            'template_id' => '1VjkbStCHkQCF_79gBLEXnFcVi-J2Y7BHLagSUmQ3U8',
            'page'        => '',
        ],
        'new_customer_access' => [
            'name'        => '新客访问提醒',
            'template_id' => 'tgGGnUsAQ-HTv_OT7QU9uS5A15eYAC8lhNTIGUB0AGc',
            'page'        => '',
        ],
        'friend_help_result' => [
            'name'        => '好友帮助结果通知',
            'template_id' => 'u_iq1hcU7s44iXqKvCvh8oaAYWNYybnH7T6sLsy0tnM',
            'page'        => 'pages/home/jz/detail/detail',
        ],

    ]
];