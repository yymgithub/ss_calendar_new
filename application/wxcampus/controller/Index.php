<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/25 0025
 * Time: 19:31
 */

namespace app\wxcampus\controller;


use app\logmanage\model\Log;
use app\wxcampus\model\CheckUser as CheckUser;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends Controller
{
    public  $stu_number;
    //微校相关信息
    private $APP_KEY = "F8D23F9B6A4AA3F2";
    private $SCHOOL_CODE = "1016145360";
    private $APP_SECRET = "8307ED503A6D58E4733D01FC459E340B";

    //检查用户是否存在
    public function checkUser($number){
        $res = Db::table("user_info")->where('work_id ='.$number)->select();
        return $res;
    }

    public function addUser($name,$number){
        Db::table('user_info')
            ->data(['name'=> $name,'work_id'=>$number,'type_id'=>0,'depart_id'=>0,'position_id'=>50,'is_delete'=>0])->insert();
    }

    //获取对应学号的user_id;
    public function getUserId($number){
        $res = Db::table('user_info')->where('work_id',$number)->column('id');
        if($res){
            return $res[0];
        }else{
            return "没有该用户";
        }

    }

    public function index(){
    //  return $this->fetch();
        $code = input('param.wxcode');
        $accessToken = $this->getAccessToken($this->APP_KEY,$this->APP_SECRET,$code);
        if($accessToken){
            $userInfo = $this->getUserInfo($accessToken);
            //检查user_info表里面有没有改用户，用学号来确认。
            $this->stu_number = $userInfo['card_number'];
            $res = $this->checkUser($userInfo['card_number']);
            //如果不存在该用户，则新增该用户
            if(!$res){
                $this->addUser($userInfo['name'],$userInfo['card_number']);
                return $this->redirect('Index/wx_policy',['wxcode'=>$code]);
            }
           // $this->assign("number",$userInfo['card_number']);
            $this->assign("name",$userInfo['name']);
            $this->assign("number",$userInfo['card_number']);
            $this->assign("wxcode", $code);
            $this->assign("userid", $this->getUserId($userInfo['card_number']));
            return $this->fetch();
        }
        else{
            echo "error";
        }
    }
    public function index0(){
        return $this->fetch();
    }

    public function wx_policy($wxcode){
        $this->assign('wxcode', $wxcode);
        return $this->fetch(); 
    }

    public function wx_loginProtocol(){
        return $this->fetch(); 
    }

    public function wx_privateh5(){
        return $this->fetch(); 
    }

    public function wx_search(){
        return $this->fetch();
    }
    public function wx_agenda(){
        return $this->fetch();
    }
    public function wx_attention(){
            $number = Request::instance()->param('number');

            $user_id = $this->getUserId($number);
            $list = Db::table('user_follow')
                ->alias(['user_follow' => 'a', 'user_info' => 'b', 'user_position' => 'c'])
                ->where('a.is_delete',0)
                ->where('a.user_id = '.$user_id)
                ->join('user_info','a.follow_id = b.id')
                ->join('user_position','b.position_id = c.id')
                ->field('a.user_id as userid, a.follow_id as followid, b.name as name, c.name as position')
                ->select();
            $this->assign('list_time_table',$list);

            return $this->fetch('wx_attention');

    }
    public function wx_me(){
        return $this->fetch();
    }
    public function wx_calendar($userid, $wxcode){
        return $this->redirect('WxCalendar/Index', ['userid'=> $userid, 'wxcode'=>$wxcode]);
    }
    public function wxquery(){
        return $this->redirect('Wxquery/Index');
    }
    public function schedule_default(){
        $params=Request::instance()->param();
        $wxcode=$params['wxcode'];
        $number = $params['number'];
        $user_id = $this->getUserId($number);
        $this->redirect('ScheduleDefault/index', ['uid'=> $user_id,'wxcode'=>$wxcode]);
    }
    //返回未关注的领导可以用来新添关注人
    public function leaderList(){
        $number = Request::instance()->param('number');
        $user_id = $this->getUserId($number);
        $condition = Db::table('user_follow')->where('is_delete = 0 AND user_id ='.$user_id)->column('follow_id');
        $list = Db::table('user_info')
            ->alias(['user_info' => 'a', 'user_position' => 'b'])
            ->where("a.id","not in",$condition)
            ->join('user_position','a.position_id = b.id')
            ->field('a.id as id, a.name as name, b.name as position')
            ->select();
        $this->assign('list_time_table',$list);
        $this->assign("userid",$user_id);
        return $this->fetch('leaderList');
    }

    //查看某个领导的日程
    public function checkDate()
    {
        $id = Request::instance()->param('followid');
        $info = Db::table('schedule_info')
            ->alias(['schedule_info' => 'a', 'user_info' => 'b', 'user_position' => 'c', 'schedule_time' => 'd', 'schedule_place' => 'e', 'schedule_item' => 'f'])
            ->where('a.is_delete',0)
            ->where("user_id",$id)
            ->join('user_info','a.user_id = b.id')
            ->join('user_position','b.position_id = c.id')
            ->join('schedule_time','a.time_id = d.id')
            ->join('schedule_place','a.place_id = e.id')
            ->join('schedule_item','a.item_id = f.id')
            ->field('d.name as time, e.name as place, f.name as item, b.id as userid')
            ->select();

        $name = Db::table('user_info')
            ->where('id',$id)
            ->column('name');

        $this->assign('who',$name[0]);
        $this->assign('info',$info);

        return $this->fetch('leader_agenda');
    }

    //增加关注人
    public function addFollow()
    {
        $followid = Request::instance()->param('followid');//被关注人
        $userid = Request::instance()->param('userid');//关注人
        $add = ['user_id'=> $userid,'follow_id'=>$followid,'is_delete'=>0];

        $res = Db::table("user_follow")->insert($add);
        if($res)
        {
            return "添加成功";
        }
        return "添加失败";
    }

    //不再关注
    public function noFollow()
    {
        $userid = Request::instance()->param('userid');
        $followid = Request::instance()->param('followid');

        $res = Db::table('user_follow')->where('user_id',$userid)->where('follow_id',$followid)
               ->setField('is_delete',1);

        if($res!=0)
        {
            return "更新成功";
        }
        return "更新失败";
    }


    private function getAccessToken($key,$secret,$wxcode){
        $url = "https://weixiao.qq.com/apps/school-auth/access-token";
        $data = array(
            "app_key" => $key,      // 微校授权唯一标识
            "wxcode" => $wxcode,            // 第一步获取到的code
            "app_secret" => $secret
        );
        $res = $this->send_post($url, json_encode($data));
        $accessData = json_decode($res,true);
        //判断是否拿到数据
        if($accessData['errcode']===0){
            return $accessData['access_token'];
        }
        else{
            return null;
        }
    }

    private function getUserInfo($accessToken){
        $url = "https://weixiao.qq.com/apps/school-auth/user-info";
        $data = array(
            "token" => $accessToken,
        );
        //dump($data);
        $userData = json_decode($this->send_post($url,json_encode($data)),true);
        //dump($userData);
        return $userData;
    }



    function send_post($url,$data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        //忽略证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ));

        $result = curl_exec($ch);
/*        if (curl_errno($ch)) {
            echo curl_error($ch);
        }*/
        curl_close($ch);
        return $result;
    }
}