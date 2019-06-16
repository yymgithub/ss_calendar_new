<?php

namespace app\manageconfig\model;
use think\Model;
use think\Db;

class LocationManage extends Model{
	//地点信息
	public function getAllLocationInfo(){
		$list = DB::table('schedule_place')->where('is_delete',0)->select();
		return $list;
	}

	//根据地点名称查询地点信息
	public function getLocationInfoByName($name){
		$res = DB::table('schedule_place')->where('name',$name)->where('is_delete',0)->select();
		return $res;
	}

	//插入一条地点信息
	public function insertLocationInfo($name){
		$data = ['name'=>$name,'create_time'=>date("Y-m-d H:i:s",time()),'update_time'=>date("Y-m-d H:i:s",time()),'is_delete'=>0];
		$info = DB::table('schedule_place')->insert($data);
		return $info;
	}

	//根据地点ID删除地点信息
	public function deleteLocationInfoByID($id){
		$info =Db::name('schedule_place')->where('id',$id)->update(['is_delete'=>1]);
		return $info;
	}

	//根据ID更新地点名称
	public function changeLocationNameByID($id,$name){
		$info =Db::name('schedule_place')->where('id',$id)->update(['name'=>$name]);
		return $info;
	}
}