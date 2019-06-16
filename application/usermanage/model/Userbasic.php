<?php
/**
 * Created by PhpStorm.
 * User: felix_zhao
 * Date: 2019/4/16
 * Time: 9:23 PM
 */
namespace app\usermanage\model;
use think\Model;
use think\Db;

class Userbasic extends Model{
    public function getinfo(){
        //页面table初始化，得到所有人员信息
        $info = Db::table('user_info')
            ->alias(['user_info' => 'ui', 'user_depart' => 'ud', 'user_position' => 'up'])
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
    public function edituserinfo($data){
        $is_add = Db::table('user_info')->where('id',$data['edit_id'])
            ->update(['name' => $data['name'],
                'work_id' => $data['work_id'],
                'type_id' => $data['type_id'],
                'depart_id' => $data['depart_id'],
                'position_id' => $data['position_id']]);
        return $is_add;
    }
    //删除人员
    public function delwhitelist($data){
        $is_delete = Db::table('user_info')->where('id', $data)
            ->update(['is_delete' => 1, 'delete_time' => date("Y-m-d H:i:s")]);
        return $is_delete;
    }

    public function insertUser($data) {
        $sqlData = ['name' => $data['name'],
            'work_id' => $data['work_id'],
            'type_id' => $data['type_id'],
            'depart_id' => $data['depart_id'],
            'position_id' => $data['position_id']];
        return Db::table('user_info')->insertGetId($sqlData);
    }

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