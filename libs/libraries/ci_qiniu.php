<?php
/**
 * @Author: binghe
 * @Date:   2017-08-08 14:25:28
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-08-09 15:39:01
 */
require_once ROOT.'vendor/autoload.php';
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
/**
* qiniu sdk
*/
class ci_qiniu 
{
    public function upload()
    {
        $accessKey='yLEyXKStHlRfmmGA_WSvGzK5AcBWyo0qQnjR195A';
        $secretKey='HttmBGDi1ewmUZz-NTyfIVszexg2KHS-JKDVsr7Q';
        $auth = new Auth($accessKey, $secretKey);
        $key=null;
        $bucket = 'imgs';
        $upToken = $auth->uploadToken($bucket,$key);
        // $upToken='yLEyXKStHlRfmmGA_WSvGzK5AcBWyo0qQnjR195A:w3-_VHMjZGRnwF6vZyB1bHwV_Mg=:eyJzY29wZSI6ImltZ3MiLCJkZWFkbGluZSI6MTUwMjI2NTI0OCwidXBIb3N0cyI6WyJodHRwOlwvXC91cC5xaW5pdS5jb20iLCJodHRwOlwvXC91cGxvYWQucWluaXUuY29tIiwiLUggdXAucWluaXUuY29tIGh0dHA6XC9cLzE4My4xMzEuNy4xOCJdfQ==';
        // $upToken='yLEyXKStHlRfmmGA_WSvGzK5AcBWyo0qQnjR195A:bVIGj0P_p9DKtd5vnkuolor0f1Y=:eyJzY29wZSI6ImltZ3MiLCJkZWFkbGluZSI6MTUwMjI2NTA5NiwidXBIb3N0cyI6WyJodHRwOlwvXC91cC5xaW5pdS5jb20iLCJodHRwOlwvXC91cGxvYWQucWluaXUuY29tIiwiLUggdXAucWluaXUuY29tIGh0dHA6XC9cLzE4My4xMzEuNy4xOCJdfQ==';

        return $upToken;


    }
    /**
     * 服务端图片移动到七牛
     */
    public function moveQiniu($path, $target)
    {
      $inc = &inc_config("qiniu");

      $client = \Qiniu\Qiniu::create(array(
        'access_key' => $inc['access_key'],
        'secret_key' => $inc['secret_key'],
        'bucket'     => $inc['bucket']
      ));
      //先删除目标文件
      $data = $client->delete($target);

      $res = $client->uploadFile($path, $target);

      if(isset($res->data['key'])) return $res->data['key'];
      return false;
    }
}