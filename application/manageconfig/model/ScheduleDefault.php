<?php


namespace app\manageconfig\model;
use think\Collection;
use think\Db;
use think\Model;

class ScheduleDefault extends Model
{
    /**
     * 获取某用户的默认日程
     *@param user  可以是uname，也可以是uid,如果是NULL或者不填则是选择所有用户的。
     *@return Collection|false|\PDOStatement|string ScheduleDefault的数组.ScheduleDefault包含的属性有<br>
     * id,user_id,place_id,time_id,item_id,day,note,<br>
     * user_name,positon,item,place,time
     */
    public static function getDefaultSchedules($user=NULL){
        $defaultSchedule=new ScheduleDefault();
        if($user==NULL){//所有用户的
            return $defaultSchedule->alias( "sd")->
            where('sd.is_delete', 0)->
            join("user_info ui", "sd.user_id=ui.id")->
            where('ui.is_delete', 0)->
            join("user_position up", "ui.position_id=up.id")->
            where('up.is_delete', 0)->
            join("schedule_place sp", "sd.place_id=sp.id")->
            join("schedule_time st", "sd.time_id=st.id")->
            join("schedule_item si", "sd.item_id=si.id")->
            field('user_id,place_id,time_id,item_id,day,note,
            ui.name as user_name,
            up.name as position,
            si.name as item,
            sp.name as place,
            sd.id as id,
            st.name as time')->
            limit(50)->order(['position_id'=>'asc','user_id'=>'asc','day'=>'asc','time_id'=>'asc'])->select();
        }else if(is_numeric($user)){
            $user_id=$user;
        }else if(is_string($user)){
            $user_id=Db::table("user_info")->where(['name'=>$user,'is_delete'=>0])->value('id');
        }
        if(!$user_id){
            return array();
        }
        return $defaultSchedule->alias( "sd")->
        where(['user_id'=>$user_id, "sd.is_delete" => 0])->
        join("user_info ui", "sd.user_id=ui.id")->
        join("user_position up", "ui.position_id=up.id")->
        where('up.is_delete', 0)->
        join("schedule_place sp", "sd.place_id=sp.id")->
        join("schedule_time st", "sd.time_id=st.id")->
        join("schedule_item si", "sd.item_id=si.id")->
        field('sd.id,user_id,place_id,time_id,item_id,day,note,
            ui.name as user_name,
            up.name as position,
            si.name as item,
            sp.name as place,
            st.name as time')->
        order(['day'=>'asc','time_id'=>'asc'])->select();//指定用户的
    }
    /**
     * 获取某人的星期几的默认日程
     * @param day 一周的第几天，从1开始，周一为1，周日为7
     * @return Collection|false|\PDOStatement|string ScheduleDefault的数组.ScheduleDefault包含的属性有<br>
     * id,place_id,time_id,item_id,day,note,<br>
     * item,place,time。<br>
     * 不包含position，如需使用，调用getPosition()方法
     */
    public static function getDefaultScheduleInDay($user_id,$day){
        $defaultSchedule=new ScheduleDefault();
        $defaultSchedules=$defaultSchedule->alias( "sd")->
        where(['user_id'=>$user_id,"day"=>$day, "is_delete" => 0])->
        join("schedule_place sp", "sd.place_id=sp.id")->
        join("schedule_time st", "sd.time_id=st.id")->
        join("schedule_item si", "sd.item_id=si.id")->
        field('sd.id,user_id,place_id,time_id,item_id,day,note,
            si.name as item,
            sp.name as place,
            st.name as time')->select();
        return $defaultSchedules;
    }
    /**return 周一 => 周日，需要的是数字的话直接调用day属性就行了*/
    public function getDay(){
        switch ($this->getData("day")){
            case 1:return '周一';
            case 2:return '周二';
            case 3:return '周三';
            case 4:return '周四';
            case 5:return '周五';
            case 6:return '周六';
            case 7:return '周日';
            default :return "";
        }
    }

    /**@param user_id 为NULL则从对象的data里取uid，不为NULL则是获取该user_id对应的uname */
    public function getUserName($user_id = NULL)
    {
        if (array_key_exists("user_name", $this->getData())) {
            return $this->getData("user_name");//兼容以前的方法
        }
        return Db::table('user_info')->
        where('id', $user_id == NULL ? $this->getData('user_id') : $user_id)->value('name');
    }

    public function getPosition($position_id = NULL)
    {
        if (array_key_exists("position", $this->getData())) {
            return $this->getData("position");//兼容以前的方法
        }
        if (array_key_exists("place", $this->getData())) {
            $position_id = $this->getData('position_id');
        } else {
            $position_id = Db::table('user_info')->where('id', $this->getData('user_id'))->value('position_id');
        }
        return Db::table("user_position")->where("id", $position_id)->value("name");
    }

    public function getTime(){
        if(array_key_exists("time",$this->getData())){
            return $this->getData("time");//兼容以前的方法
        }
        return Db::table('schedule_time')->where('id', $this->getData('time_id'))->value('name');
    }

    public function getPlace(){
        if(array_key_exists("place",$this->getData())){
            return $this->getData("place");//兼容以前的方法
        }
        return Db::table('schedule_place')->where('id', $this->getData('place_id'))->value('name');
    }

    public function getItem(){
        if(array_key_exists("item",$this->getData())){
            return $this->getData("item");//兼容以前的方法
        }
        return Db::table('schedule_item')->where('id', $this->getData('item_id'))->value('name');
    }


    /**@param $user 可以是字符串，代表用户名，也可以是整数或字符串整数，代表user_id*/
    public function setUserId($user){
        if(is_numeric($user)){//因为uid也是字符串，所以把这层判断放前面
        }else if(is_string($user)){
            $user=Db::table("user_info")->where(['name'=>$user,'is_delete'=>0])->value('id');
            if(!$user){
                throw new \InvalidArgumentException('用户不存在');
            }
        }else{
            throw new \InvalidArgumentException('user只能是字符串或者整数');
        }
        $this->data('user_id',$user);
    }
    public function setDay($day){
        $this->data('day',$day);
    }

    public function setTime($time){
        $time_id = Db::table('schedule_time')->where('name', $time)->value('id');
        if (empty($time_id)) {
            $time_id=Db::table('schedule_time')->insertGetId(['name'=>$time,'is_delete'=>0]);
        }
        $this->data('time_id',$time_id);
    }

    /**
     * 检查之前是否已经有一样的默认日程了
     * @throws \InvalidArgumentException 存在相同时间段的话抛出
     */
    public function checkSameTimeDefaultSchedule(){
        $res = Db::table('schedule_default')->
            where('user_id',$this->getData('user_id'))->
            where('day',$this->day)->
            where('time_id', $this->getData('time_id'))->
            where('is_delete', 0)->find();
        if ($res != null){
            throw new \InvalidArgumentException('已存在该时间段的默认日程，可点击编辑进行修改,创建时间：'.$res['create_time']);
        }
    }

    public function setPlace($place){
        $place_id=Db::table('schedule_place')->where(['name'=>$place,'is_delete'=> 0])->value('id');
        if(empty($place_id)){//如果是之前不存在的地点，则新建一个
            $place_id=Db::table('schedule_place')->insertGetId(['name'=>$place,'is_delete'=>0]);
        }
        $this->data('place_id',$place_id);
    }

    public function setItem($item){
        $item_id=Db::table('schedule_item')->where(['name'=>$item,'is_delete'=> 0])->value('id');
        if(empty($item_id)){//如果是之前不存在的事项，则新建一个
            $item_id=Db::table('schedule_item')->insertGetId(['name'=>$item,'is_delete'=>0]);
        }
        $this->data('item_id',$item_id);
    }
    public function setNote($note){
        $this->data('note',$note);
    }
}