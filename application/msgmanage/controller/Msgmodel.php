<?php
/**
 * Created by PhpStorm.
 * User: 84333
 * Date: 2019/4/14
 * Time: 0:50
 */

namespace app\msgmanage\controller;


use app\common\controller\Common;

class Msgmodel extends Common
{
    /*
    *story:查询消息模板
    *负责人：吴珏
    */
    public function index(){
        $model = model('Template');
        $search = "";
        $status = -2;
        $range = -2;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
        }
        if (isset($_GET['range'])) {
            $range = $_GET['range'];
        }
        if($status==-2 && $range == -2){
            $templateItems = $model->getAllTemplates();
            $this->assign('templateItems',$templateItems);
            return $this->fetch();
        }
        else{
            if($status==1 && $search==""){
                $templateItems = $model->getAllTemplates();
            }
            else if($status==2 && $search==""){
                $templateItems = $model->getAllTemplatesDelete();
            }
            else if($status==1 && $range==1){
                $templateItems = $model->getItemByTitle($search);
            }
            else if($status==1 && $range==2){
                $templateItems = $model->getItemByContent($search);
            }
            else if($status==2 && $range==1){
                $templateItems = $model->getItemByTitleDelete($search);
            }
            else if($status==2 && $range==2){
                $templateItems = $model->getItemByContentDelete($search);
            }
            else if($status==1 && $range==0){
                $templateItems = $model->getAllItems($search);
            }
            else if($status==2 && $range==0){
                $templateItems = $model->getAllItemsDelete($search);
            }
            if ($templateItems == null) {
                $this->error("搜索项不存在，请重新尝试");
            }
            else{
                $this->assign('templateItems',$templateItems);
                return $this->fetch();
            }
        }
    }

    public function loadTemplate()
    {
        $template = model('Template');
        $templates = $template->getAllTemplates();
        return $templates;
    }
    
    public function searchTemplate($search,$status,$range)
    {
        $model = model('Template');
        if($status==-1){
            $this->error("请选择查询状态");
        }
        else if($range==-1){
            $this->error("请选择查询范围");
        }
        else{
            if($status==2 && $search==""){
                $isHasTitle = $model->getAllTemplates();
            }
            else if($status==1 && $search==""){
                $isHasTitle = $model->getAllTemplatesDelete();
            }
            else if($status==1 && $range==1){
                $isHasTitle = $model->getItemByTitleDelete($search);
            }
            else if($status==1 && $range==2){
                $isHasTitle = $model->getItemByContentDelete($search);
            }
            else if($status==2 && $range==1){
                $isHasTitle = $model->getItemByTitle($search);
            }
            else if($status==2 && $range==2){
                $isHasTitle = $model->getItemByContent($search);
            }
            else if($status==1 && $range==0){
                $isHasTitle = $model->getAllItems($search);
            }
            else if($status==2 && $range==0){
                $isHasTitle = $model->getAllItemsDelete($search);
            }
            if ($isHasTitle == null) {
                $this->error("搜索项不存在，请重新尝试");
            }
            else{
                $this->assign('templateItems',$isHasTitle);
                return $this->fetch();
            }
        }
    }
    public function enableTemplate(){
        $id = $_POST['id'];
        $tit = $_POST['tit'];
        $model = model('Template');
        $isHasSame = $model->strictGetItemByTitle($tit);
        if($isHasSame==null){
            $res = $model->renewTemplate($id);
            if($res == 1)
                $this->success("恢复成功");
            else
                $this->success("恢复失败");
        }
        else{
            $this->success("已存在相同标题，恢复失败");
        }
        
    }
    /*
     *story:添加消息模板
     *负责人：佟起
     */
    public function  addTemplate()
    {
        $tit = $_POST['tit'];
        $con = $_POST['con'];
        $regTit = '/^[\x{4e00}-\x{9fa5}a-z][\x{4e00}-\x{9fa5}a-z\d\s]{0,29}[\x{4e00}-\x{9fa5}a-z\d]$/u'; 
        if(preg_match($regTit,$tit) && strlen($tit)<=140){  //验证标题格式 
            $model = model('Template');
            $isHasSame = $model->strictGetItemByTitle($tit);
            //$isHasSame = null;
            if ($isHasSame == null) {
                $res = $model->insertTemplate($tit, $con);
                if($res){
                    $this->success("新增成功");
                }
                else{
                    $this->error("添加失败，请重新尝试");
                }
            }
            else{
                $this->error("名称重复");
            }
        }
        else{
            $this->error("添加失败，请重新尝试"); 
        }
    }
    /*
     *story:删除消息模板
     *负责人：张骁雄
     */
    public function  deleteTemplate(){
        $id = $_POST['id'];
        $model = model('Template');
        $res = $model->clearTemplate($id);
        if($res == 1)
            $this->success("删除成功");
        else
            $this->success("删除失败");
    }
    /*
    *story:修改消息模板
    *负责人：张骁雄
    */
    public function modifyTemplate(){
        $id = $_POST['id'];
        $des = $_POST['des'];
        $model = model('Template');
        $res = $model->updateTemplate($id,$des);
        if($res==1)
            $this->success("修改成功");
        else
            $this->success("修改失败");
    }

    /*
    *story:根据消息模板向用户发送提醒消息（刘玄）
    细分story：发送消息提醒
    *负责人：刘玄
    */
    public function remind()
    {
        $user_id = $_POST['user_id'];
        $position = model('Template');
        $res=$position->remind($user_id);
        if($res == 1)
            $this->success("发送消息提醒成功");
        else
            $this->success("发送消息提醒失败");
    }
     /*
    *story:根据消息模板向用户发送提醒消息（刘玄）
    细分story：取消发送消息提醒
    *负责人：刘玄
    */
    public function cancelremind()
    {
        $user_id = $_POST['user_id'];
        $position = model('Template');
        $res=$position->cancelremind($user_id);
        if($res == 1)
            $this->success("取消消息提醒成功");
        else
            $this->success("取消消息提醒成功");
    }
    /*
    *story:根据消息模板向用户发送提醒消息（刘玄）
    细分story：向客户端发送消息内容
    *负责人：刘玄
    */

    public function remindToApp()
    {
     

        $res= model('Template');
        $dateres = $res->remindToApp();
        $res_success = json_encode($dateres);
    
        header('Content-Type:application/json');//这个类型声明非常关键
        return $res_success;
    

    }

}
