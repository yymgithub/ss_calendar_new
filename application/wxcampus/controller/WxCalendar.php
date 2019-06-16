<?php

namespace app\wxcampus\controller;

use think\Controller;
use app\logmanage\model\Log as LogModel;
use think\Validate;
use think\Request;
use think\Db;
//描述：用户更新自己的日程
//1.用户通过点击日程选项，会显示自己当天的日程；
//2.用户通过点击加号按钮，跳转到新建日程页面；
//3.用户点击当天的日程，跳转到修改日程页面；
//4.用户点击上面的两个箭头，跳转到前天和明天的日程页面， 从而修改其他时间的日程

//bug list:
//1. 导航栏还没做好(Index默认要传wxcode, 很麻烦) 
//更新： 传入wxcode也没用, 还是提示error
//2. 新增日程默认的日程是当天， 即使页面是其他天
//3. 更改页面的选项没有随着用户的选中的事项来改变
//4. 新增时数据库里create_time未被修改,而是null
//5. 修改日程时create_time被修改，但update_time未被修改，而是null
//6. 可以修改过去的日程
//7. 可以修改期限以外的日程

class CalendarValidator extends Validate
{
    protected $rule =[
        'pagenum' => 'require|number|>=:0',
        'id' => 'require|number|checkTable:schedule_info,id',
        'user_id' => 'require|number',
        'date' => 'require|date',
        'time_id' => 'require|number|checkTable:schedule_time,id',
        'place_id' => 'require|number|checkTable:schedule_place,id',
        'item_id' => 'require|number|checkTable:schedule_item,id',
    ];
    protected $scene = [
        'getSchedule' => [
            'pagenum'
        ],
        'create' => [
            'date',
            'time_id',
            'place_id',
            'item_id'
        ],
        'update' => [
            'id',
            'date',
            'time_id',
            'place_id',
            'item_id'
        ],
        'delete' => [
            'id'
        ]
    ];
    protected function checkTable($value, $rule, $data, $field){
        $params = explode(',', $rule);
        return Db::name($params[0])->where($params[1], $value)->count() == 1;
    }
    private function getDdlTimestamp(){
        //TODO
        return time() + 5*24*60*60;
    }
    protected function checkDate($value, $rule, $data, $field){
        $given = strtotime($value);
        $now = strtotime(date('Y-m-d'));
        $ddl = $this->getDdlTimestamp();
        return $now <= $given && $given <= $ddl;
    }
}

class WxCalendar extends Controller
{
    //apis
    private $uid;
    private $wxcode;
    protected function getUserId(){
        return $this->uid;
    }
    private function getDdl(){
        //TODO
        return date('Y-m-d', time() + 24*60*60);
    }
    protected function getOneDaySchedule($timestamp){
        $page = Db::name('schedule_info')
            ->where('user_id', $this->getUserId())
            ->where('date', date('Y-m-d', $timestamp))
            ->where('is_delete', 0)
            ->select();
        return $page;
    }
    protected function getSchedule($userid, $scheduleId){
        return Db::name('schedule_info')
            ->where('user_id', $userid)
            ->where('id', $scheduleId)
            ->find();
    }
    //返回所有相关字段, 保证当一个项被删除后, 依然可以显示.
    protected function getAllScheduleItems(){
        return Db::name('schedule_item')
            ->select();
    }
    protected function getAllSchedulePlaces(){
        return Db::name('schedule_place')
            ->select();
    }
    protected function getAllScheduleTimes(){
        return Db::name('schedule_time')
            ->select();
    }
    protected function getScheduleItems(){
        return Db::name('schedule_item')
        ->where('is_delete', 0)
        ->select();
    }
    protected function getSchedulePlaces(){
        return Db::name('schedule_place')
        ->where('is_delete', 0)
        ->select();
    }
    protected function getScheduleTimes(){
        return Db::name('schedule_time')
        ->where('is_delete', 0)
        ->select();
    }
    protected function json($method, $success, $message){
        return json([
            'method' => $method,
            'success'=> $success,
            'message'=> $message
        ]);
    }

    public function create($userid){
        $data = [
            'user_id'       => $userid,
            'date'          => input('post.date'),
            'time_id'       => input('post.time_id'),
            'place_id'      => input('post.place_id'),
            'item_id'       => input('post.item_id'),
            'note'          => input('post.note'),
            'create_time'   => time()
        ];
        //检查输入是否有效
        $valid = $this->validate($data, 'app\wxcampus\controller\CalendarValidator.create');
        if($valid !== true){//验证失败
            return $this->json('create', false, dump($valid, false));
        }
        //插入
        $id = Db::name('schedule_info')->insertGetId($data);
        //记录
        $logRec = new LogModel;
        $logRec->recordLogApi($userid, 2, 0,'schedule_info', $id);
        return $this->json('create', true, 'success');
    }
    public function update($userid){
        $data = [
            'id'            => input('post.id'),
            'user_id'       => $userid,
            'date'          => input('post.date'),
            'time_id'       => input('post.time_id'),
            'place_id'      => input('post.place_id'),
            'item_id'       => input('post.item_id'),
            'note'          => input('post.note'),
            'update_time'   => time()
        ];
        $valid = $this->validate($data, 'app\wxcampus\controller\CalendarValidator.update');

        if($valid !== true){//验证失败
            return $this->json('update', false, dump($valid,false));;
        }
        //找到修改了的参数
        $origin = Db::name('schedule_info')
            ->where('id', $data['id'])
            ->where('user_id', $data['user_id'])
            ->find();
        if($origin == NULL){
            return $this->json('update', false, '找不到要修改的参数');;
        }
        $diff = [];
        foreach($data as $key=>$val){
            if($origin[$key] !== $val){
                $diff[$key] = [$origin[$key], $val];
            }
        }
        //更新
        $success = Db::name('schedule_info')
            ->where('id', $data['id'])
            ->where('user_id', $data['user_id'])
            ->update($data);
        if($success !== 1){//更新失败
            return $this->json('update', false, '数据库插入失败!');
        }
        //记录日志
        $logRec = new LogModel;
        $logRec->recordLogApi($userid, 3, 0,'schedule_info', [$data['id'] => $diff]);
        return $this->json('update', true, 'success');
    }

    public function delete($userid, $id){
        //检查是否有效
        $valid = $this->validate($data, 'app\wxcampus\controller\CalendarValidator.delete');
        if($valid !== true){
            return $this->json('delete', false, dump($valid, false));
        }
        //删除
        $success = Db::name('schedule_info')
            ->where('id', $id)
            ->where('user_id', $userid)
            ->update([
                'is_delete'     => true,
                'delete_time'   => time()
            ]);
        if($success != 1){//删除失败
            return $this->json('delete', false, '数据库删除失败!');
        }
        //记录日志
        $logRec = new LogModel;
        $logRec->recordLogApi($userid, 4, 0,'schedule_info', [$id]);
        return $this->json('delete', true, 'success');
    }
    //Views
    protected $items;
    protected $places;
    protected $times;
    public function Index($wxcode, $userid, $date = NULL){
        $this->uid = $userid;
        $this->wxcode = $wxcode;
        if($date == NULL)$date = date('Y-m-d');
        $this->items = $this->getAllScheduleItems();
        $this->places = $this->getAllSchedulePlaces();
        $this->times = $this->getAllScheduleTimes();
        $this->assign('date', date('Y-m-d',strtotime($date)));
        $this->assign('cells', $this->getScheduleDisplayArray(strtotime($date)));
        $this->assign('left', url('index', ['wxcode' => $wxcode, 'userid'=>$userid, 'date'=> date('Y-m-d',strtotime($date)-24*60*60)]));
        $this->assign('right', url('index', ['wxcode' => $wxcode, 'userid'=>$userid, 'date'=> date('Y-m-d',strtotime($date)+24*60*60)]));
        $this->assign('userid', $userid);
        $this->assign('wxcode' ,$wxcode);
        return $this->fetch("index/wx_calendar");
    }
    public function getScheduleDisplayArray($timestamp){
        assert($this->items != NULL);
        assert($this->places!= NULL);
        assert($this->times != NULL);
        $cells = [];
        $schedules = $this->getOneDaySchedule($timestamp);
        foreach ($schedules as $sched){
            $timeid = $sched['time_id'];
            $itemid = $sched['item_id'];
            $placeid = $sched['place_id'];
            $timename = '';
            $itemname = '';
            $placename = '';
            foreach($this->times as $timeunit) {
                if($timeunit['id'] == $timeid){
                    $timename = $timeunit['name'];
                }
            }
            foreach($this->items as $itemunit) {
                if($itemunit['id'] == $itemid){
                    $itemname = $itemunit['name'];
                }
            }
            foreach($this->places as $placeunit) {
                if($placeunit['id'] == $placeid){
                    $placename = $placeunit['name'];
                }
            }
            if(!array_key_exists($timename, $cells)){
                $cell = [
                    'time' => $timename,
                    'data' => []
                ];
                $cells[$timename] = $cell;
            }
            $dataItem = [
                'item' => $itemname,
                'note' => $sched['note'],
                'place'=> $placename,
                'id'   => $sched['id']
            ];
            array_push($cells[$timename]['data'], $dataItem);
        }
        return $cells;
    }
    protected function detail($wxcode){
        $this->assign('wxcode' , $wxcode);
        $this->assign('items', $this->getScheduleItems());
        $this->assign('times', $this->getScheduleTimes());
        $this->assign('places', $this->getSchedulePlaces());
        $this->assign('maxlength', 200);
        return $this->fetch("index/wx_detail");
    }
    public function updatePage($wxcode, $userid, $id){
        $sched = $this->getSchedule($userid, $id);
        $this->assign('userid', $userid);
        $this->assign('scheduleid', $id);
        $this->assign('date', $sched['date']);
        $this->assign('note', $sched['note']);
        $this->assign('title', '修改日程');
        $this->assign('confirmid', 'update-btn');
        return $this->detail($wxcode);
    }
    public function createPage($wxcode, $userid){
        $this->assign('userid', $userid);
        $this->assign('scheduleid', -1);
        $this->assign('date', date('Y-m-d'));
        $this->assign('note', '');
        $this->assign('title', '添加日程');
        $this->assign('confirmid', 'create-btn');
        return $this->detail($wxcode);
    }
    public function postTest($userid){
        $data = [
            'user_id'       => $userid,
            'method'        => input('post.method'),
            'date'          => input('post.date'),
            'time_id'       => input('post.time_id'),
            'place_id'      => input('post.place_id'),
            'item_id'       => input('post.item_id'),
            'note'          => input('post.note'),
            'create_time'   => time()
        ];
        var_dump($data);
    }
    //route
    public function myRedirect($url){
        $this->redirect($url);
    }
}