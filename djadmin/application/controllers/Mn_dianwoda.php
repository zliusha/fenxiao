<?php
/**
 * 点我达模拟测试
 */
class Mn_dianwoda extends CI_Controller
{
    public $tradeno = '';

    public function __construct()
    {
        parent::__construct();
        $tradeno = $this->input->get('tradeno');
        if ($tradeno) {
            $this->tradeno = $tradeno;
        }
    }

    /**
     * 模拟接单
     */
    public function accept()
    {
        $ci_dianwoda = new ci_dianwoda();
        $params = [
            'order_original_id' => $this->tradeno,
        ];
        pdump($params);
        pdump($ci_dianwoda->call(ci_dianwoda::MN_ACCEPT, $params));
    }
    /**
     * 模拟取货
     */
    public function arrive()
    {
        $ci_dianwoda = new ci_dianwoda();
        $params = [
            'order_original_id' => $this->tradeno,
        ];
        pdump($params);
        pdump($ci_dianwoda->call(ci_dianwoda::MN_ARRIVE, $params));
    }
    /**
     * 模拟取货
     */
    public function fetch()
    {
        $ci_dianwoda = new ci_dianwoda();
        $params = [
            'order_original_id' => $this->tradeno,
        ];
        pdump($ci_dianwoda->call(ci_dianwoda::MN_FETCH, $params));
    }
    /**
     * 模拟完成
     */
    public function finish()
    {
        $ci_dianwoda = new ci_dianwoda();
        $params = [
            'order_original_id' => $this->tradeno,
        ];
        pdump($ci_dianwoda->call(ci_dianwoda::MN_FINISH, $params));
    }
}
