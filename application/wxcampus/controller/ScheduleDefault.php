<?php

namespace app\wxcampus\controller;

use app\logmanage\model\Log;
use think\Controller;
use app\manageconfig\model\ScheduleDefault as ScheduleDefaultModel;
use think\Exception;
use think\Request;
use think\Db;
use think\Model;

class ScheduleDefault extends Controller
{

    /**管理默认日程的界面*/
    public function index($uid, $wxcode)
    {
        $this->assign("uid", $uid);
        $this->assign("userid", $uid);
        $this->assign("wxcode", $wxcode);
        $this->assign("defaultSchedules", ScheduleDefaultModel::getDefaultSchedules($uid));
        return $this->fetch();
    }

    /**
     *添加默认日程界面
     */
    public function wx_add_schedule_default($uid, $wxcode)
    {
        $this->assign("uid", $uid);
        $this->assign("userid", $uid);
        $this->assign("wxcode", $wxcode);
        $this->assign("title", "添加默认日程");
        return $this->fetch();
    }

    /**
     * 添加默认日程动作
     */
    public function addDefaultSchedule($uid)
    {
        $param = Request::instance()->post();
        $res = $this->validate($param, 'app\manageconfig\validate\ScheduleDefault');//验证是否符合规范
        if (true !== $res) {
            return json(['code' => 403, 'msg' => '参数不符合规则：' . $res]);
        }
        $schedule = new ScheduleDefaultModel();
        try {
            $schedule->setUserId($uid);
            $schedule->setDay($param['day']);
            $schedule->setTime($param['time']);
            $schedule->checkSameTimeDefaultSchedule();
            $schedule->setPlace($param['place']);
            $schedule->setItem($param['item']);
            $schedule->setNote("备注：".$param['note']);
        } catch (\InvalidArgumentException $e) {
            return json(['code' => $e->getCode(), 'msg' => $e->getMessage()]);
        }
        $schedule->is_delete = 0;
        $schedule->update_time = date("Y-m-d H:i:s");
        if ($schedule->save()) {
            $log = new Log();
            $log->recordLogApi($uid, 2, 0, "schedule_default", [$schedule->id]);
            return json(['code' => 1, 'msg' => 'success']);
        } else {
            return json(['code' => -1, 'msg' => '添加失败，发生未知错误']);
        }
    }

    /**
     *修改默认日程界面
     */
    public function wx_update_schedule_default($uid, $item_id, $day, $time, $place, $item, $note, $wxcode, $id, $place_id)
    {
        $this->assign("uid", $uid);
        $this->assign("userid", $uid);
        $this->assign("item_id", $item_id);//默认事项id
        $this->assign("time", $time);//待更改默认事项时间
        $this->assign("place", $place);//待更改默认事项地点
        $this->assign("item", $item);//待更改默认事项内容
        $this->assign("day", $day);
        $this->assign("note", $note);//待更改默认事项备注
        $this->assign("wxcode", $wxcode);
        $this->assign("title", "更新默认日程");
        $this->assign("id", $id);
      	$this->assign("place_id", $place_id);
        return $this->fetch();
    }

    public function updateDefaultSchedule($id, $uid)
    {
        $param = Request::instance()->post();

        $time = $param['time'];
        $place = $param['place'];
        $item = $param['item'];
        $day = $param['day'];
        $note = $param['note'];

        $place_id = Db::table('schedule_place')->where('name', $place)->find()['id'];
        $item_id = Db::table('schedule_item')->where('name', $item)->find()['id'];
        $time_id = Db::table('schedule_time')->where('name', $time)->find()['id'];

        //schedule_default表里是直接存的day和note的数据，而不是id

        $info = Db::name('schedule_default')->where('id', $id)
            ->update(['day'=>$day, 'note'=>$note ,'time_id'=>$time_id ,'user_id'=>$uid, 'place_id'=>$place_id, 'item_id'=>$item_id, "update_time"=>date("Y-m-d H:i:s")]);
        if($info){
            return json(['code' => 1, 'msg' => 'success']);
        } else {
            return json(['code' => -1, 'msg' => '修改失败，发生未知错误']);
        }

    }

    /**
     *删除默认日程界面
     */
    public function wx_delete_schedule_default($id, $uid, $wxcode)
    {
        //执行删除的操作
        $result = Db::name("schedule_default")->where('id', $id)->update(['is_delete' => 1, "delete_time" => date("Y-m-d H:i:s")]);
        //header("Location: schedule_default/index");
        /*if ($result) {
            return json(['code' => 1, 'msg' => 'success']);
        } else {
            return json(['code' => -1, 'msg' => '删除失败，发生未知错误']);
        }*/
        $this->redirect('ScheduleDefault/index',['uid'=>$uid, 'wxcode'=>$wxcode]);
        //return $this->index($uid, $wxcode);
        //$this->index()

    }
    /**
     * 修改默认日程界面
     */
    public function wx_edit_schedule_default($uid, $wxcode, $schedule){
        $this->assign("uid", $uid);
        $this->assign("userid", $uid);
        $this->assign("wxcode", $wxcode);
        $this->assign("note", $schedule['note']);
        $this->assign("title", "修改默认日程");
        return $this->fetch();
    }


}


