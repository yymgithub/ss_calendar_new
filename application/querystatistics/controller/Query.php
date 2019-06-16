<?php
/**
 * Date: 2019/5/13
 * Time: 12:57
 * Author: yang kang
 */

namespace app\querystatistics\controller;
use app\common\controller\Common;
use think\Session;

use app\querystatistics\model\ScheduleSearch as SchedulModel;
use think\controller;
use think\Db;
use think\Request;

class Query extends Common{

    public function index(){
		//if(Session::get('admin_id')){
		//	$this->redirect('querystatistics/Managerqueryschedule/index');
		//}
    //else{
        $schedul_model = new SchedulModel();
        $info = $schedul_model->searchAllInfo();
        $this->assign('info',$info);
        return $this->fetch();
    //}
	}
}

