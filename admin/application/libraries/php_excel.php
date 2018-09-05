<?php
require_once APPPATH.'/libraries/PHPExcel.php';
class php_excel {

    private $CI;
    private $objExcel;
    private $objWriter;
    private $objReader;
    private $objActiveSheet = null;
    private $properties = array(
        'title'   =>'www.wadao.com',
        'creator' =>'www.wadao.com',
        'category'=>'www.wadao.com',
        'subject' =>'www.wadao.com',
        'keywords'=>'www.wadao.com',
        'manager' =>'www.wadao.com',
        'company' =>'www.wadao.com',
        'description'   =>'www.wadao.com',
        'lastModifiedBy'=>'www.wadao.com'
    );
    private $rows = 0; //表格数据已写入行数(第一个表格坐标<0, 1>)
    private $cols = 0; //数据的列数
    private $cols_key = array();//设置列数据的顺序
    private $path = UPLOAD_PATH.'excel/';//生成excel的目录

    public function __construct($obj='writer'){

        //设置缓存方式

//        $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
//        $cacheSettings = array('memoryCacheSize'=>'128MB');
//        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
        $cacheSettings = array();
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);


        if($obj=='reader'){
            $this->objReader = new PHPExcel_Reader_Excel2007();
        } else {
            $this->objExcel = new PHPExcel();
            $this->objWriter = new PHPExcel_Writer_Excel2007($this->objExcel);

        }

    }

    //目前暂时后台发送短信读取excel使用
    public function readExcel($path, $start=0, $sheet=0){

        /*
        $php_reader = new PHPExcel_Reader_Excel2007();
        $PHPExcel = $php_reader->load($path);
        $currentSheet = $PHPExcel->getSheet($sheet);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $mobile_arr = array();
        for ($currentRow = $start; $currentRow <= $allRow; $currentRow ++) {
            $mobile = $currentSheet->getCellByColumnAndRow(0, $currentRow)->getValue();
            array_push($mobile_arr, $mobile);
        }
        return $mobile_arr;
        */
    }

    /**
     * 读取excel数据
     * @param $path   文件路径 绝对路径
     * @param int $start 开始读取excel的行数
     * @param int $sheet 要读取数据的工作表
     * @return array
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function getExcelData($path='', $start=1, $sheet=0){

        if(!file_exists($path)) return false;

        $this->objReader->setReadDataOnly(true);//设置这个，只读取数据，不用读取excel的属性,避免不必要的错误

        $objExcelFile = @$this->objReader->load($path);

        $currentSheet = $objExcelFile->getSheet($sheet);

        $rows = $currentSheet->getHighestRow();
        $cols = PHPExcel_Cell::columnIndexFromString($currentSheet->getHighestColumn());

        $data = array();
        for($i=$start; $i<=$rows; $i++){ //行
            $tmp = array();
            for($j=0; $j<$cols; $j++){//列
                $tmp[] = trim($currentSheet->getCellByColumnAndRow($j, $i)->getValue());
            }
            array_push($data, $tmp);
        }
        return $data;
    }

    /**
     * 设置excel属性
     * @param array $properties 属性键值
     */
    public function setProperties($properties=array()){

        if(empty($properties) || !is_array($properties)) $properties = array();

        $objProp = $this->objExcel->getProperties();
        foreach($this->properties as $key => $val){

            $method = 'set'.ucfirst($key);
            if(in_array($key, $properties))
                $objProp->$method($properties[$val]);
            else
                $objProp->$method($val);
        }
    }

    /**
     * 设置工作表 和 首行标题
     * @param array $title 关联数组
     * @param int $index
     */
    public function setActiveSheet(array $title=array(),$index=0){
        if($this->objActiveSheet == null) $this->_setSheet($index);
        $this->objActiveSheet->setTitle('www.wadao.com');

        $this->cols = 0;
        foreach($title as $k => $v){

            $this->objActiveSheet->setCellValueByColumnAndRow($this->cols, 1, $v);
            array_push($this->cols_key, $k);
            $this->cols++;
        }
        $this->rows++;
    }

    /**
     * 设置导出的数据
     * @param array $data  //二维数组
     */
    public function setSheetData($data=array()){
        foreach($data as $rk => $rv){  //行数
            $rows = ++$this->rows;
            $cols = 0;
            foreach($this->cols_key as $ck => $cv){
                $this->objActiveSheet->setCellValueByColumnAndRow($cols, $rows, $rv[$cv].' ');//加空格变成字符串，防止数字过长，导出时变成科学计数法
                $cols++;
            }
        }

    }

    /**
     * 生成excel文件
     * @param string $filename
     * @return string
     * @throws PHPExcel_Writer_Exception
     */

    public function save($filename=''){

        $ext = '.xlsx';
        if(empty($filename)) $filename = date('Y-m-d');
        if(!is_dir($this->path)) @mkdir($this->path, 0777, true);

        $file_path = $this->path.$filename.$ext;

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_path.'"');
        header('Cache-Control: max-age=0');
        $this->objWriter->save($file_path);

        return UPLOAD_URL.'excel/'.$filename.$ext;
    }
    /**
     * 设置当前的sheet
     * @param int $index sheet索引
     * @return PHPExcel_Worksheet  getActiveSheet();
     * @throws PHPExcel_Exception
     */
    private function _setSheet($index=0){
        $this->objActiveSheet = $this->objExcel->setActiveSheetIndex($index);
    }


}