<?php
/**
 * @Author: binghe
 * @Date:   2017-02-17 14:54:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-02-17 15:13:45
 */
/**
* 二维码生成器
*/
use Grafika\Color;
use Grafika\Grafika;

class Qr_api extends CI_Controller
{
    // 输出qr
    // 参数 d  内容生成
    // 参数 l  M 级别 [L','M','Q','H']
    // daxiao 4 可设置 2,4,8
    public function index()
    {
        $params['data'] =  $this->input->get('d') ? $this->input->get('d') : false;

        if ($params['data']) {
            header("Content-Type: image/png");
            $ci_qrcode=new ci_qrcode();
            // 'otpauth://totp/Blog?secret=JPA6EM4NZSFOD7RL';
            $params['level'] = $this->input->get('l') ? $this->input->get('l') : "M";
            $params['size'] = ($this->input->get('s') and is_numeric($this->input->get('s'))) ? $this->input->get('s') : 4;
            if(empty($this->input->get('sn')))  $params['savename'] = $this->input->get('sn');

            // $params['bg'] = array(255, 255, 255);
            // $params['fg'] = array(0, 0, 0);
            // 保存到本地
            // unlink(UPLOAD_PATH.'tes.png');
            // $params['savename'] = UPLOAD_PATH.'tes.png';
            // $params['show'] = 1;

            $ci_qrcode->generate($params);
            // echo $params['savename'];
        } else {
//            show_404();
        }
    }

    public function download()
    {
        $params['data'] =  $this->input->get('d') ? $this->input->get('d') : false;

        if ($params['data']) {
            header("Content-Type: image/png");
            $ci_qrcode=new ci_qrcode();
            $params['level'] = $this->input->get('l') ? $this->input->get('l') : "M";
            $params['size'] = ($this->input->get('s') and is_numeric($this->input->get('s'))) ? $this->input->get('s') : 4;

            $file_name = md5($params['data']).'.png';
            $file_path = UPLOAD_PATH.$file_name;
            $params['savename'] = $file_path;

            $ci_qrcode->generate($params);

            header( "Content-type:  application/octet-stream ");
            header( "Accept-Ranges:  bytes ");
            header( "Content-Disposition:  attachment; filename= {$file_name}");
            $size = @readfile($file_path);
            header( "Accept-Length: " .$size);
            @unlink($file_path);
        } else {
            show_404();
        }
    }

  /**
   *
   */
    public function table_qr()
    {
        // 二维码指向URL地址
        $qr_path = urldecode($this->input->post_get('qr_path'));
        $title = urldecode($this->input->post_get('title'));


        if (!$qr_path) {
          echo '';exit;
        }

        $qr_path = UPLOAD_PATH.$qr_path;

        $ci_grafika = new ci_grafika();
        $editor = Grafika::createEditor();

        $editor->open($image1, $qr_path);

        $editor->resizeExactWidth($image1, 176);
        $editor->resizeExactHeight($image1, 176);

        // 新画布长宽
        $image = Grafika::createBlankImage(210, 210);

        $transparent = new Color('#ffffff');
        $transparent->setAlpha(1); //填充透明颜色
        $editor->fill($image, $transparent, 0, 0);

        $editor->blend($image, $image1, 'normal', 1, 'bottom-center');

        //超过15个字 ...
        $len = mb_strlen($title);
        $title = mb_substr($title,0,15);
        if($len > 15)
          $title .= '...';

        $editor->text($image ,$title,12,45,12,new Color("#3c3c3c"),STATIC_PATH.'djadmin/fonts/vista.ttf',0 );

        $editor->save($image, $qr_path);

        echo $qr_path;
    }

  /**
   * 生成二维码并输出路径
   * @return mixed
   */
    public function gen_qrcode()
    {

        $params['data'] =  $this->input->get('d') ? $this->input->get('d') : false;

        if ($params['data']) {

          $params['level'] = $this->input->get('l') ? $this->input->get('l') : "M";
          $params['size'] = ($this->input->get('s') and is_numeric($this->input->get('s'))) ? $this->input->get('s') : 4;
          if(!empty($this->input->get('sn')))  $params['savename'] = $this->input->get('sn');

          $ci_qrcode = new ci_qrcode();
          $ci_qrcode->generate($params);

          echo $params['savename'];
        } else {
              show_404();
        }
    }
}
