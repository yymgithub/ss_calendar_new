<?php
/**
 * Created by PhpStorm.
 * User: 84333
 * Date: 2019/4/14
 * Time: 0:40
 */

namespace app\adminmanage\controller;
use app\common\controller\Common;
use app\adminmanage\model\ManageInfo as ManageInfoModel;
use think\Config;
use \think\Request;

class Adminbasic extends Common
{
    // admins list
  public function index(){
    // get admin group
    $auth = new Auth();
    
    // get all admin
    // show 10 admins per page
    //$admin_list = ManageInfoModel::paginate(100);
    $resp['current_status'] = -1;
    $admin_list = ManageInfoModel::paginate(10);
    if(input('?get.status')){
      $status = Request::instance()->param('status');
      //dump($status); die;
      if ((int)$status >= 0){
        $admin_list = ManageInfoModel::where('is_delete',$status) -> paginate(10);
        $resp['current_status'] = (int)$status;
      }
    }
    foreach ($admin_list as $k => $v){
      //var_dump($v['id']);
      $_group_title = $auth -> getGroups($v['id']);
      if ($_group_title){
        $group_title = $_group_title[0]['title'];
        $v['group_title'] = $group_title;
      }else{
        $v['group_title'] = '未设置组权限';
      }
    }
    $resp['admin_list'] = $admin_list;
    $resp['status_list'] = Config::get('STATUS');
    $this -> assign('resp', $resp);
    return $this -> fetch('index');
  }
  // add an admin member
  public function add(){
    // date_default_timezone_set("Asia/Shanghai");
    // dump(date_default_timezone_get()); die;
    // check method is Post
    if (request() -> isPost()){
      $data = [
        'username' => input('admin_name'),
        'telephone' => input('admin_phone'),
        'password' => input("admin_password"),
        'is_delete' => 0,
        'update_time' => date('Y-m-d H:i:s')
      ];

      $validate = \think\Loader::validate('ManageInfo');
      if(!$validate -> scene('add') -> check($data)){
        $this -> error($validate -> getError());
        die;
      }else{
        // 加密管理员帐号
        $data['password'] = md5($data['password']);
      }

      if(db('manage_info') -> insert($data)){
        //insert into group access
        $current_user = db('manage_info') -> where('username',input('admin_name')) -> find();
        $add_grp_acc = db('manage_auth_group_access') -> insert(['uid' => $current_user['id'], 'group_id' => input('group_id')]);
        if($add_grp_acc){
          return $this->success('添加管理员成功', 'index');
        }else{
          return $this->error('添加管理员失败');
        }
      }else{
        return $this->error('添加管理员失败');
      }




      return;

    }
    $auth_group_list = db('manage_auth_group') -> where('status', 1) -> select();
    $this -> assign('auth_group_list', $auth_group_list);
    return $this -> fetch('add');
  }

  // edit admin info
  public function edit(){
    if (request() -> isPost()){
      $id = input('id');
      $admin = db('manage_info') -> find($id);

      $data = [
        'id' => input('id'),
        'username' => input('admin_name'),
        'update_time' => date('Y-m-d H:i:s')
      ];
      if(input('admin_password')){
          $data['password'] = md5(input('admin_password'));
      }else{
          $data['password'] = $admin['password'];
      }

      if(input('admin_phone')){
          $data['telephone'] = input('admin_phone');
      }else{
          $data['telephone'] = $admin['telephone'];
      }

      $validate = \think\Loader::validate('ManageInfo');
        if (!$validate -> scene('edit') -> check($data)){
          $this -> error($validate -> getError()); die;
      }

      $save = db('manage_info') -> update($data);
        if($save !== false){
          //insert into group access
          //dump(['uid' => input('id'), 'group_id' => input('group_id')]); die;
          $add_grp_acc = db('manage_auth_group_access') -> where(array('uid' => input('id'))) -> update(['group_id' => input('group_id')]);
          if($add_grp_acc !== false){
            return $this->success('编辑管理员成功', 'index');
          }else{
            return $this->error('编辑管理员失败');
          }
          $this->success('修改成功', 'index');
        }else{
          $this->error('修改失败');
        }

      return;
    }

    $id = input('id');
    $admin = db('manage_info') -> find($id);
    $this -> assign('admin', $admin);
    $auth_group_list = db('manage_auth_group') -> where('status', 1) -> select();
    $this -> assign('auth_group_list', $auth_group_list);
    //query group access
    $auth_grp_access = db('manage_auth_group_access') -> where(array('uid' => $id)) -> find();
    $this -> assign('group_id', $auth_grp_access['group_id']);
    return $this -> fetch('edit');
  }

  // delete admin
  public function del(){
    $id = input('id');
    $admin = db('manage_info') -> find($id);

    if($admin['is_delete'] == 0){
      // 更新数据表中的数据
      if (db('manage_info')->where('id',$id)->update(['is_delete' => 1, 'delete_time' => date('Y-m-d H:i:s')])){
        $this -> success('删除成功', 'index');
      }else{
        $this -> error('删除失败', 'index');
      }

    }else{
      $this -> error('删除失败', 'index');
    }
  }

  public function recover(){
    $id = input('id');
    $admin = db('manage_info') -> find($id);
    if($admin['is_delete'] == 1){
      // 更新数据表中的数据
      if (db('manage_info')->where('id',$id)->update(['is_delete' => 0])){
        $this -> success('恢复成功', 'index');
      }else{
        $this -> error('恢复失败', 'index');
      }

    }else{
      $this -> error('恢复失败', 'index');
    }
  }
}