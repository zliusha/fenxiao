<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/3/30
 * Time: 10:04
 */
use Service\Cache\MealShopAreaTableCache;
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopAreaDao;

class Shop_area_api extends wm_service_controller
{
    /**
     * 区域列表
     */
    public function area_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        //子账号权限
        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table = $wmShardDb->tables['meal_shop_area'];
        $p_conf->order = 'sort asc,id desc';
        $p_conf->where = " aid={$this->s_user->aid} AND shop_id={$shop_id}";
        $count = 0;
        $list = $page->getList($p_conf, $count);

        $v_rules = array(
            array('type' => 'time', 'field' => 'time', 'format' => 'Y-m-d H:i:s'),
        );

        $data['total'] = $count;
        $data['rows'] = convert_client_list($list, $v_rules);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 所有区域
     */
    public function get_all_area()
    {
        $shop_id = intval($this->input->get('shop_id'));
        //子账号权限
        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $shop_area_list = $mealShopAreaDao->getAllArray(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]);

        $this->json_do->set_data($shop_area_list);
        $this->json_do->out_put();
    }

    /**
     * 区域添加
     */
    public function area_add()
    {
        $rule = [
            ['field' => 'name', 'label' => '区域名称', 'rules' => 'trim|required|max_length[30]'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        //子账号权限
        if (!$this->is_zongbu) {
            $f_data['shop_id'] = $this->currentShopId;
        }
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);

        $f_data['aid'] = $this->s_user->aid;
        $f_data['time'] = time();

        $id = $mealShopAreaDao->create($f_data);
        if ($id > 0) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2', '保存失败');
    }

    /**
     * 区域编辑
     */
    public function area_edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '区域ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'name', 'label' => '区域名称', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        //子账号权限
        if (!$this->is_zongbu) {
            $f_data['shop_id'] = $this->currentShopId;
        }

        $f_data['aid'] = $this->s_user->aid;
        $f_data['time'] = time();
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);

        $ret = $mealShopAreaDao->update(['name' => $f_data['name']], ['id' => $f_data['id'], 'aid' => $this->s_user->aid]);
        if ($ret !== false) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2', '保存失败');
    }

    /**
     * 区域删除
     */
    public function area_del()
    {
        $id = $this->input->post('id');
        if (!is_numeric($id)) {
            $this->json_do->set_error('002', '参数错误');
        }
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $m_shop_area = $mealShopAreaDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);
        if (!$m_shop_area) {
            $this->json_do->set_error('002', '记录不存在');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_area_table = $mealShopAreaTableDao->getOne(['shop_area_id' => $id, 'status > ' => 0, 'aid' => $this->s_user->aid]);
        if ($m_area_table) {
            $this->json_do->set_error('002', '当前区域有桌位正在使用，禁止删除');
        }

        $ret = $mealShopAreaDao->delete(['id' => $id, 'aid' => $this->s_user->aid]);
        if ($ret !== false) {
            $mealShopAreaTableDao->delete(['shop_area_id' => $id, 'aid' => $this->s_user->aid]);
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2', '删除失败');
    }

    /**
     * 区域桌位列表
     */
    public function area_table_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        $shop_area_id = intval($this->input->get('shop_area_id'));
        $title = $this->input->get('title');
        //子账号权限
        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $area_where = "aid={$this->s_user->aid} AND shop_id={$shop_id} ";
        $area_table_where = "aid={$this->s_user->aid} AND shop_id={$shop_id}";
        if ($shop_area_id > 0) {
            $area_where .= " AND id={$shop_area_id}";
            $area_table_where .= " AND shop_area_id={$shop_area_id}";
        }
        if (!empty($title)) {
            $area_table_where .= " AND name like '%{$title}%'";
        }

        $shop_area_list = $mealShopAreaDao->getAllArray($area_where);
        $shop_area_table_list = $mealShopAreaTableDao->getAllArray($area_table_where);

        $v_rules = [
            ['type' => 'time', 'field' => 'time', 'format' => 'Y-m-d H:i:s'],
            ['type' => 'img', 'field' => 'qr_img'],
        ];
        $shop_area_table_list = convert_client_list($shop_area_table_list, $v_rules);

        foreach ($shop_area_list as $key => $shop_area) {
            $shop_area_list[$key]['area_table_list'] = array_search_list($shop_area_table_list, 'shop_area_id', $shop_area['id']);
            $shop_area_list[$key]['time'] = date('Y-m-d H:i:s', $shop_area['time']);
        }

        $this->json_do->set_data($shop_area_list);
        $this->json_do->out_put();
    }

    /**
     * 桌位添加
     */
    public function area_table_add()
    {
        $rule = [
            ['field' => 'name', 'label' => '桌位名称', 'rules' => 'trim|required'],
            ['field' => 'number', 'label' => '建议人数', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_area_id', 'label' => '区域ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $m_shop_area = $mealShopAreaDao->getOne(['id' => $f_data['shop_area_id'], 'aid' => $this->s_user->aid]);
        if (!$m_shop_area) {
            $this->json_do->set_error('002', '区域记录不存在');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        //判断name是否重复
        $m_area_table = $mealShopAreaTableDao->getOne(['aid' => $this->s_user->aid, 'shop_area_id' => $f_data['shop_area_id'], 'name' => $f_data['name']]);
        if ($m_area_table) {
            $this->json_do->set_error('002', '桌位名称重复');
        }

        $f_data['aid'] = $this->s_user->aid;
        $f_data['shop_id'] = $m_shop_area->shop_id;
        $f_data['time'] = time();

        $id = $mealShopAreaTableDao->create($f_data);
        if ($id > 0) {
            //生成二维码
            $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);
            $this->_gen_qrcode($m_shop_area_table);

            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2', '保存失败');
    }

    /**
     * 桌位编辑
     */
    public function area_table_edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '桌位ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'name', 'label' => '桌位名称', 'rules' => 'trim|required'],
            ['field' => 'number', 'label' => '建议人数', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_area_id', 'label' => '区域ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $m_shop_area = $mealShopAreaDao->getOne(['id' => $f_data['shop_area_id'], 'aid' => $this->s_user->aid]);
        if (!$m_shop_area) {
            $this->json_do->set_error('002', '区域记录不存在');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        //判断name是否重复
        $m_area_table = $mealShopAreaTableDao->getOne("aid={$this->s_user->aid} AND shop_area_id={$f_data['shop_area_id']} AND name='{$f_data['name']}' AND id !={$f_data['id']}");
        if ($m_area_table) {
            $this->json_do->set_error('002', '桌位名称重复');
        }

        $f_data['shop_id'] = $m_shop_area->shop_id;
        $f_data['time'] = time();

        $ret = $mealShopAreaTableDao->update($f_data, ['id' => $f_data['id'], 'aid' => $this->s_user->aid]);
        if ($ret !== false) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2', '保存失败');
    }

    /**
     * 桌位删除
     */
    public function area_table_del()
    {
        $id = $this->input->post('id');
        if (!is_numeric($id)) {
            $this->json_do->set_error('002', '参数错误');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);
        if (!$m_shop_area_table) {
            $this->json_do->set_error('002', '记录不存在');
        }

        if ($m_shop_area_table->status == 1) {
            $this->json_do->set_error('002', '当前桌位正在使用中');
        }

        $ret = $mealShopAreaTableDao->delete(['id' => $id, 'aid' => $this->s_user->aid]);
        if ($ret !== false) {
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2', '删除失败');
    }

    /**
     * 桌位详情
     */
    public function area_table_info()
    {
        $id = $this->input->post_get('id');
        if (!is_numeric($id)) {
            $this->json_do->set_error('002', '参数错误');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);

        if (!$m_shop_area_table) {
            $this->json_do->set_error('002', '暂无记录');
        }

        $m_shop_area = MealShopAreaDao::i($this->s_user->aid)->getOne(['id' => $m_shop_area_table->shop_area_id, 'aid' => $this->s_user->aid]);
        //区域名称
        $m_shop_area_table->shop_area_name = $m_shop_area->name;

        //没有图片路径则生成二维码图片
        if (!empty($m_shop_area_table->qr_img)) {
            $m_shop_area_table->qr_img = conver_picurl($m_shop_area_table->qr_img);
        }
        $order = [];
        //当前桌位批次详细信息
        if ($m_shop_area_table->cur_order_table_id > 0) {

            $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
            $order['order_table'] = $mealOrderTableDao->getOneArray(['id' => $m_shop_area_table->cur_order_table_id, 'aid' => $this->s_user->aid]);

            $meal_order_query_bll = new meal_order_query_bll();
            $list = $meal_order_query_bll->query_all(['aid' => $this->s_user->aid, 'order_table_id' => $m_shop_area_table->cur_order_table_id]);

            //待审核订单
            $order['audit_order']['list'] = array_find_list($list, 'api_status', 1);
            $order['audit_order']['total_money'] = array_sum(array_map(function ($val) {return $val['pay_money'];}, $order['audit_order']['list']));
            $order['audit_order']['total_num'] = array_sum(array_map(function ($val) {return $val['total_num'];}, $order['audit_order']['list']));
            //已取消订单
            $order['cancel_order']['list'] = array_find_list($list, 'api_status', 8);
            $order['cancel_order']['total_money'] = array_sum(array_map(function ($val) {return $val['pay_money'];}, $order['cancel_order']['list']));
            $order['audit_order']['total_num'] = array_sum(array_map(function ($val) {return $val['total_num'];}, $order['cancel_order']['list']));
            //已下厨订单
            $order['done_order']['list'] = array_find_list($list, 'api_status', 2);
            $order['done_order']['total_money'] = array_sum(array_map(function ($val) {return $val['pay_money'];}, $order['done_order']['list']));
            $order['done_order']['total_num'] = array_sum(array_map(function ($val) {return $val['total_num'];}, $order['done_order']['list']));
            //已完成订单
            $order['complete_order']['list'] = array_find_list($list, 'api_status', 9);
            $order['complete_order']['total_money'] = array_sum(array_map(function ($val) {return $val['pay_money'];}, $order['complete_order']['list']));
            $order['complete_order']['total_num'] = array_sum(array_map(function ($val) {return $val['total_num'];}, $order['complete_order']['list']));

            //开单时间
            $m_order = MealOrderDao::i($this->s_user->aid)->getOne(['aid' => $this->s_user->aid, 'order_table_id' => $m_shop_area_table->cur_order_table_id], 'time', 'id asc');
            $order['start_time'] = $m_order ? date('Y-m-d H:i:s', $m_order->time) : '';

        }

        $data['table_info'] = $m_shop_area_table;
        $data['order_info'] = $order;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 标记已下载
     */
    public function is_down_update()
    {
        $id = $this->input->post_get('id');
        if (!is_numeric($id)) {
            $this->json_do->set_error('002', '参数错误');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);

        if (!$m_shop_area_table) {
            $this->json_do->set_error('002', '暂无记录');
        }

        $mealShopAreaTableDao->update(['is_down' => 1], ['id' => $id, 'aid' => $this->s_user->aid]);

        $this->json_do->set_msg('成功');
        $this->json_do->out_put();
    }

    /**
     * 重新生成二维码
     */
    public function regen_qrcode()
    {
        $id = $this->input->post_get('id');
        if (!is_numeric($id)) {
            $this->json_do->set_error('002', '参数错误');
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);

        if (!$m_shop_area_table) {
            $this->json_do->set_error('002', '暂无记录');
        }

        $qr_img = $this->_gen_qrcode($m_shop_area_table);
        $m_shop_area_table->qr_img = UPLOAD_URL . $qr_img;
        //更新下载状态
        $mealShopAreaTableDao->update(['is_down' => 0], ['id' => $id, 'aid' => $this->s_user->aid]);

        $this->json_do->set_data($m_shop_area_table);
        $this->json_do->out_put();
    }

    /**
     * 生成二维码，合成图片
     * @param null $meal_shop_area_table_model  桌位model
     * @return bool|null
     */
    private function _gen_qrcode($meal_shop_area_table_model = null)
    {

        if (!$meal_shop_area_table_model) {
            return null;
        }

        //跳转路径
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 8, 6));
        if(in_array(ENVIRONMENT, ['development','testing']))
        {
            $base_url = $this->get_shop_base_url('http');
        }else {
            $base_url = $this->get_shop_base_url();
        }
        $redirect_url = $base_url . "/meal/?table_id={$meal_shop_area_table_model->id}&code={$code}&shop_id={$meal_shop_area_table_model->shop_id}";

        $abs_dir = UPLOAD_PATH . 'qrcode';
        if (!is_dir($abs_dir)) {
            @RecursiveMkdir($abs_dir, '0755');
        }
        $qr_path = 'qrcode/' . md5($redirect_url) . '.png';

        $params = [
            'd' => $redirect_url,
            'l' => 'M',
            's' => 10,
            'sn' => UPLOAD_PATH . $qr_path,
        ];

        $qr_url_params = http_build_query($params);
        $qr_url = $url = DJADMIN_URL . 'qr_api/gen_qrcode?' . $qr_url_params;
        //生成·二维码
        @file_get_contents($qr_url);
        $m_shop_area = MealShopAreaDao::i($this->s_user->aid)->getOne(['id' => $meal_shop_area_table_model->shop_area_id, 'aid' => $this->s_user->aid]);
        $title = '';
        if ($m_shop_area) {
            $title = "桌位：{$m_shop_area->name} {$meal_shop_area_table_model->name}";
        }
        //合成图片
        $url = $url = DJADMIN_URL . 'qr_api/table_qr?qr_path=' . urlencode($qr_path)
        . "&title=" . urlencode($title);

        $abs_qr_path = trim(@file_get_contents($url), ' ');

        if ($abs_qr_path) {
            $ci_qiniu = new ci_qiniu();
            $target = 'meal/qrcode/' . md5($qr_url) . '.png';

            $qr_img = $ci_qiniu->moveQiniu($abs_qr_path, $target);
            MealShopAreaTableDao::i($this->s_user->aid)->update(['qr_img' => $qr_img, 'code' => $code], ['id' => $meal_shop_area_table_model->id, 'aid' => $this->s_user->aid]);
            //删除本地服务器图片
            @unlink($abs_qr_path);

            //清楚桌位信息缓存
            $mealShopAreaTableCache = new MealShopAreaTableCache(['table_id' => $meal_shop_area_table_model->id, 'code' => $code]);
            $mealShopAreaTableCache->delete();

            return $qr_img;
        }
        return null;
    }

    /**
     *单个二维码下载
     */
    public function download()
    {

        $id = intval($this->input->get('id'));
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);

        if ($m_shop_area_table) {
            $file_name = md5($m_shop_area_table->qr_img) . '.png';
            $file_path = UPLOAD_PATH . 'qrcode/' . $file_name;
            $remote_url = UPLOAD_URL . $m_shop_area_table->qr_img;
            download_remote_img($file_path, UPLOAD_PATH . 'qrcode', $remote_url);

            $filename = basename($file_name);

            header("Content-type:  application/octet-stream ");
            header("Accept-Ranges:  bytes ");
            header("Content-Disposition:  attachment; filename= {$filename}");
            $size = @readfile($file_path);
            header("Accept-Length: " . $size);
            @unlink($file_path);

            //更新下载状态
            $mealShopAreaTableDao->update(['is_down' => 1], ['id' => $id, 'aid' => $this->s_user->aid]);
        }
    }

    /**
     * 店铺二维码批量下载
     */
    public function zip_download()
    {
        $shop_id = intval($this->input->get('shop_id'));

        //子账号权限
        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $shop_area_table_list = $mealShopAreaTableDao->getAllArray(['shop_id' => $shop_id, 'aid' => $this->s_user->aid], 'id,qr_img');

        $zip_filename = 'qrcode' . date('YmdH') . ".zip";
        $zip_filepath = UPLOAD_PATH . $zip_filename;

        //生成zip文件
        $zip = new ZipArchive();
        $zip->open($zip_filepath, ZipArchive::CREATE); //打开压缩包
        foreach ($shop_area_table_list as $area_table) {
            $file_name = md5($area_table['qr_img']) . '.png';
            $file_path = UPLOAD_PATH . 'qrcode/' . $file_name;
            $remote_url = UPLOAD_URL . $area_table['qr_img'];
            download_remote_img($file_path, UPLOAD_PATH . 'qrocde', $remote_url);

            if (file_exists($file_path)) {
                $zip->addFile($file_path, basename($file_name));
            }

        }
        $zip->close();

        //下载zip文件
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary");
        header("Content-Disposition:  attachment; filename= {$zip_filename}");
        $size = @readfile($zip_filepath);
        header("Accept-Length: " . $size);
        //删除zip文件
        @unlink($zip_filepath);
    }

}
