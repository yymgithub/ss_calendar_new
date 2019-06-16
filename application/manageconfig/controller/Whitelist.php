<?php
/**
 * Created by PhpStorm.
 * User: 84333
 * Date: 2019/4/14
 * Time: 0:25
 */

namespace app\manageconfig\controller;

use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use app\common\controller\Common;
use app\logmanage\model\Log as LogModel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use think\Request;


class Whitelist extends Common
{
    public function index(){
        $whitelist = model('Whitelist');
        $info = $whitelist->getinfo();
        foreach($info as $key=>$value){
            if ($info[$key]['type_id'] == 0){
                $info[$key]['type'] = '普通用户';
            }elseif ($info[$key]['type_id'] == 1){
                $info[$key]['type'] = '院领导';
            }elseif ($info[$key]['type_id'] == 2){
                $info[$key]['type'] = '部门领导';
            }elseif ($info[$key]['type_id'] == 3){
                $info[$key]['type'] = '系领导';
            }
        }
        $this->assign('info',$info);

        //添加人员模态框传值
        $depart = $whitelist->getdepart();
        $this->assign('depart',$depart);
        $position = $whitelist->getposition();
        $this->assign('position',$position);
        return $this->fetch();
    }

    public function addwhitelist(){
        $data = input('post.');
        if (empty($data['name']) || empty($data['work_id'])){
            $this->error('输入不可为空');
        }
        $whitelist = model('Whitelist');

        $exist_work_id = $whitelist->exist_work_id($data['work_id']);
        if ($exist_work_id){
            $this->error('该工号已存在');
        }
//        if ($data['type']!=0 && $data['type']!=1 && $data['type']!=2 && $data['type']!=3){
//            $this->error('请输入正确的用户类型');
//        }
//
//        $exist_depart = $whitelist->exist_depart($data['depart_id']);
//        if (!$exist_depart){
//            $this->error('该部门不存在');
//        }
//        $exist_position = $whitelist->exist_position($data['position_id']);
//        if(!$exist_position){
//            $this->error('该职位不存在');
//        }
        $is_add = Db::table('white_list')->insert($data);

        if ($is_add){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    public function delwhitelist(){
        $whitelist = model('Whitelist');
        $data = input('post.');
        $is_delete = $whitelist->delwhitelist($data);
        if($is_delete){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    public function editwhitelist(){
        $whitelist = model('Whitelist');

        $data = input('post.');
        if (empty($data['name']) || empty($data['work_id'])){
            $this->error('输入不可为空');
        }
        
        $is_add = $whitelist->editwhitelist($data);
        if ($is_add){
            $this->success('修改成功！');
        }else{
            $this->error('修改失败！');
        }
    }

    /*
    创建： 翁嘉进
    功能： 实现清空白名单操作
    实现： 1.判断管理员是否登录 
           2.若无登录，则跳转至登录界面（通过继承Common类实现） 
           3.清空白名单 
           4.记录操作日志 
           5.返回结果
    */

    public function clearwhitelist(){
        $whitelist = model('Whitelist');                                  // 调用白名单数据模型
        $ret_date = $whitelist->clearwhitelist();                         // 通过模型进行清空操作
        $is_clear = $ret_date["is_clear"];
        $clear_ids = $ret_date["clear_ids"];
        $logmodel = new LogModel();                                       // 调用操作日志数据模型
        $uid = ADMIN_ID;                                                  // 管理员ID
        $type = 4;                                                        // 操作类型：删除（清空）
        $table = 'white_list';                                             // 操作数据表
        $field = '[All whitelist items, total:'.$is_clear.$clear_ids.']';      // 删除的主键列表, 不是学号
        $logmodel->recordLogApi ($uid, $type, 1, $table, $field);            // 需要判断调用是否成功

        if ($is_clear >= 0){
            $this->success('修改成功！');
        }else{
            $this->error('修改失败！');
        }
    }

    //---------------------------------------------------------------
    /*
    responser: 陈国强
    Created：2019/05/15
    some implemented function：
    insertAllUser($data) ： 向 user_info 数据表插入信息
    findUserByWorkId($workId)： 通过工号查找该用户是否存在
    excelInput() ： 向 user_info 数据表插入信息
    修改了前端，添加了隐藏的按钮
    */

    public function excelInput(){
        // 初始化reader类
        $reader = new Xlsx();
        try {
            // 检测是否有Excel文件
            $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $sheet = $spreadsheet->getActiveSheet();
        $sqlData = array();
        $count = 0;
        // 使用model
        $excelData = model("Whitelist");
        // 遍历Excel表格，将数据存入sqlData
        foreach ($sheet->getRowIterator(2) as $row) {
            $tmp = array();
            foreach ($row->getCellIterator() as $cell) {
                $tmp[] = $cell->getFormattedValue();
            }
            // 重复的不添加
            if ($excelData->findUserByWorkId($tmp[1]) == null) {
                $tmp = ['name' => $tmp[0],
                    'work_id' => $tmp[1],
                    'type_id' => $tmp[2],
                    'depart_id' => $tmp[3],
                    'position_id' => $tmp[4]];
                $sqlData[$count++] = $tmp;
            }
            else{
                continue;
            }
        }
        $addFlag = false ;
        //如果从Excel获取的数组为空，即用户提交的Excel表格与已有数据库全部重复
        if (empty($sqlData)){
            //$addFlag = $excelData->insertAllUser($sqlData);
            $this->success('添加成功,但Excel表格与数据库内容相同，请检查Excel表格是否已经提交过');
        }
        else{
            $addFlag = $excelData->insertAllUser($sqlData);
        }
        //echo  $sqlData[0];
        if ($addFlag) {
            $this->success('添加成功,自动跳转');
        } else {
            $this->error('添加失败');
        }
    }

    //---------------------------------------------------------------


    //---------------------------------------------------------------
    /*
    responser: 陈国强
    Created：2019/05/16
    some implemented function：
    getinfo()：获取白名单信息
    excelInput() ： 向 user_info 数据表插入信息
    */
    public function excelOutput()
    {
        $info = model("Whitelist")->getinfo();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', '用户姓名')
            ->setCellValue('C1', '工号/学号')
            ->setCellValue('D1', '用户类型：')
            ->setCellValue('E1', '所属部门')
            ->setCellValue('F1', '职位');
        $departdict = array(0 => "普通用户", 1 => "院领导", 2 => "部门领导", 3 => "系领导");
        $spreadsheet->getActiveSheet()->setTitle('用户信息');
        //dump($info);
        $i = 2; //从第二行开始
        foreach ($info as $data) {
            $spreadsheet->getActiveSheet()
                ->setCellValue('A' . $i, $data['id'])
                ->setCellValue('B' . $i, $data['ui_name'])
                ->setCellValue('C' . $i, $data['work_id'])
                ->setCellValue('D' . $i, $departdict[$data['type_id']])
                ->setCellValue('E' . $i, $data['ud_name'])
                ->setCellValue('F' . $i, $data['up_name']);

            $i++;
        }
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('A')
            ->setWidth(10);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('B')
            ->setWidth(20);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('C')
            ->setWidth(20);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('D')
            ->setWidth(15);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('E')
            ->setWidth(20);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('F')
            ->setWidth(15);

        $spreadsheet->getActiveSheet()->getStyle('A1:F' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="白名单信息.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }


    //---------------------------------------------------------------

}
