<?php
namespace app\querystatistics\model;
use think\Model;
use think\Db;


class ScheduleSearch extends Model
{
    //public function getAllSchedule(){
    //    $list = DB::table('schedule_info')->where('is_delete',0)->select();
    //    return $list;
    //}

    //public function searchUserInfo($name){
        // 根据用户名查询用户id
    //    $list = DB::table('user_info')->where('name',$name)->where('is_delete',0)->select();
    //    return $list;
    //}

    //public function searchSchedule($id){
        // 根据用户名查询用户日程
    //    $list = DB::table('schedule_info')->where('user_id',$id)->where('is_delete',0)->select();
    //    return $list;
    //}

    public function searchAllInfo(){
        {
            $info = Db::table('schedule_info')
                ->alias(['schedule_info' => 'a', 'user_info' => 'b', 'user_position' => 'c', 'schedule_time' => 'd', 'schedule_place' => 'e', 'schedule_item' => 'f'])
                ->where('a.is_delete',0)
                ->join('user_info','a.user_id = b.id')
                ->join('user_position','b.position_id = c.id')
                ->join('schedule_time','a.time_id = d.id')
                ->join('schedule_place','a.place_id = e.id')
                ->join('schedule_item','a.item_id = f.id')
                ->field('a.id, b.name as name, c.name as position, date,d.name as time, e.name as place, f.name as item, b.id as userid')
                ->order('date desc')
				->select();
            return $info;
        }
    }

}