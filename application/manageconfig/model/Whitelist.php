<?php
/**
 * Created by PhpStorm.
 * User: 84333
 * Date: 2019/4/16
 * Time: 16:22
 */

namespace app\manageconfig\model;
use think\Model;
use think\Db;

class Whitelist extends Model{
    public function getinfo(){
        //页面table初始化，得到所有白名单人员的信息
        $info = Db::table('white_list')
            ->alias(['white_list' => 'ui', 'user_depart' => 'ud', 'user_position' => 'up'])
            ->where('ui.is_delete',0)
            ->where('ud.is_delete',0)
            ->where('up.is_delete',0)
            ->join('user_depart','ui.depart_id = ud.id')
            ->join('user_position','ui.position_id = up.id')
            ->field('ui.id,ui.name as ui_name,ui.work_id,ui.type_id,ui.depart_id,ui.position_id,ud.name as ud_name,up.name as up_name')
            ->select();
        return $info;
    }
    //得到所有部门信息
    public function getdepart(){
        $depart = Db::table('user_depart')->where('is_delete',0)->field('id,name')->select();
        return $depart;
    }
    //得到所有职位信息
    public function getposition(){
        $position = Db::table('user_position')->where('is_delete',0)->field('id,name')->select();
        return $position;
    }
    //编辑人员信息
    public function editwhitelist($data){
        $is_add = Db::table('white_list')->where('id',$data['edit_id'])
            ->update(['name' => $data['name'],
                'work_id' => $data['work_id'],
                'type_id' => $data['type_id'],
                'depart_id' => $data['depart_id'],
                'position_id' => $data['position_id']]);
        return $is_add;
    }
    //删除人员
    public function delwhitelist($data){
        $is_delete = Db::table('white_list')->where('id',$data['del_id'])
            ->update(['is_delete' => 1]);
        return $is_delete;
    }
    //根据depart_id判断部门是否存在
    public function exist_depart($depart_id){
        $isexist = Db::table('user_depart')->where('id',$depart_id)->where('is_delete',0)->find();
        if ($isexist==null){
            return false;
        }else{
            return true;
        }
    }
    //根据position_id判断职位是否存在
    public function exist_position($position_id){
        $isexist = Db::table('user_position')->where('id',$position_id)->where('is_delete',0)->find();
        if ($isexist==null){
            return false;
        }else{
            return true;
        }
    }
    //判断工号是否已存在且有效
    public function exist_work_id($work_id){
        $isexist = Db::table('white_list')->where('work_id',$work_id)->where('is_delete',0)->find();
        if ($isexist==null){
            return false;
        }else{
            return true;
        }
    }

    /*
    创建： 翁嘉进
    功能： 清空白名单操作
    实现： 1.连接表——白名单 
           2.查询带清空的数据
           3.软清空白名单
           4.记录清空个数 
           5.返回结果
    */
    public function clearwhitelist(){
    	$list = db("white_list")->where("is_delete",0)->select();
        $is_clear = 0;
        $clear_ids = "[";

        foreach($list as $data){
            $postdata = [
                    "user_id" => $data["user_id"] * (-1),
                    "is_delete" => 1,
                        ];
            $cul = db("white_list")->where("id",$data["id"])->update($postdata);
            $is_clear += $cul;
            $clear_ids = $clear_ids . $data["id"] . ", ";
        }
        $clear_ids = $clear_ids . "]";
        $ret_date = [
            "clear_ids" => $clear_ids,
            "is_clear" => $is_clear,
        ];
        return $ret_date;
    }

    //---------------------------------------------------------------
    /*
    responser: 陈国强
    Created：2019/05/15
    insertAllUser($data) ： 向 user_info 数据表插入信息
    findUserByWorkId($workId)： 通过工号查找该用户是否存在
    */
    public function insertAllUser($data) {
        return Db::table('user_info')->insertAll($data);
    }

    public function findUserByWorkId($workId) {
        return Db::table('user_info')
            ->where('work_id', $workId)
            ->where('is_delete', 0)
            ->find();
    }
}

    //---------------------------------------------------------------