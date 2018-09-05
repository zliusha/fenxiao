<?php
/**
 * @Author: binghe
 * @Date:   2017-09-21 15:57:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-09-26 10:10:56
 */
require_once ROOT.'vendor/autoload.php';
/**
* phpexcel
*/
class ci_phpexcel 
{
    
    /**
     * xls导出
     * @param  array $fields  需导出的字段
     * @param  array $data     需导出的数据
     * @param  string $filename 文件名，无需扩展名
     */
    public static function down($fields,$data,$filename)
    {
        $phpexcel = new PHPExcel();
        $phpexcel->getProperties()->setTitle('export')->setDescription('none');
        $phpexcel->setActiveSheetIndex(0);
        $col=0;
        foreach($fields as $k=>$v)
        {
            $phpexcel->getActiveSheet()->setCellValueByColumnAndRow($col,1,$v);
            $col++;
        }
        $row=2;
        foreach ($data as $item) {
            $col=0;
            foreach ($fields as $k1 => $v1) {
                $phpexcel->getActiveSheet()->setCellValueByColumnAndRow($col,$row,$item[$k1]);
                $col++;
            }
            $row++;
        }
        $objWriter=PHPExcel_IOFactory::createWriter($phpexcel,'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
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
    public static function getExcelData($path='', $start=1, $sheet=0){

        if(!file_exists($path)) return false;
        $objReader = PHPExcel_IOFactory::createReader('Excel5');

        $objReader->setReadDataOnly(true);//设置这个，只读取数据，不用读取excel的属性,避免不必要的错误

        $objExcelFile = @$objReader->load($path);

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
}