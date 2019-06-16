<?php
namespace app\querystatistics\controller;

use app\common\controller\Common;
use think\Controller;
use think\Request;
use think\Db; 

/**
*	@	Purpose:
*	 统计用户日程信息的类
*	@Author:	刘博楠
*	@Date:	2019/4/17
*	@Time:	20:00
*/

class Statistics extends Common
{
		/**
		*	@Purpose:
		*	 执行一次查询
		*	@Method	Name:	index()
		*
		*	@Author:	刘博楠
		*
		*	@Return:	查询返回值（结果集对象）
		*/
    public function index()
    {
				$list = DB::query("SELECT distinct user_info.id id,user_info.name name,user_depart.name depart_name,user_position.name user_posname 
													FROM user_info,schedule_info,user_depart,user_position 
													where user_info.id=schedule_info.user_id and 
													user_info.is_delete=0 and 
													schedule_info.is_delete=0 and 
													user_info.depart_id=user_depart.id and 
													user_info.position_id=user_position.id and 
                                                    user_depart.is_delete=0 and 
                                                    user_position.is_delete=0");
				$this->assign('arealist', $list);
				return $this->fetch('index');
    }

		/**
		*	@Purpose:
		*	 执行一次查询
		*	@Method	Name:	statisticsFun()
		*
		*	@Author:	刘博楠
		*
		*	@Return:	查询返回值（结果集对象）
		*/
   	public function statisticsFun(){
				$id=$_GET['id'];
				$list = DB::query("SELECT user_info.name name,schedule_info.date date,schedule_time.name time,schedule_place.name place,schedule_item.name item 
													FROM schedule_info,schedule_item,schedule_place,schedule_time,user_info 
													where schedule_info.time_id=schedule_time.id and 
													schedule_info.item_id=schedule_item.id 
													and schedule_info.place_id=schedule_place.id and 
													schedule_info.user_id=user_info.id and 
													schedule_info.is_delete=0 and 
													schedule_item.is_delete=0 and 
													schedule_place.is_delete=0 and 
													schedule_time.is_delete=0 and 
													schedule_info.user_id=".$id);
				$this->assign('arealist', $list);
				return $this->fetch('info');
    }
  
}