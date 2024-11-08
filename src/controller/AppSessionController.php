<?php
namespace mwtech\thinkphp_app_session\controller;

use think\facade\Request;
use cmf\controller\HomeBaseController;
use mwtech\thinkphp_app_session\model\ProjectUserBaseInfoModel;

class AppSessionController extends HomeBaseController
{
    protected $appSessionHandler;  // 应用级的session句柄

    public function initialize()
    {
        parent::initialize();
        $this->appSessionHandler = app('http')->getName();  // 使用ThinkPHP的应用名称
    }

    /**
     * 获取应用级别的session变量
     * @param $sessionVarHandler 不包含应用名称的session变量名，例如user.id，在获取session变量时，会自动加上应用名称
     * @return mixed
     */
    protected function get_app_session($sessionVarHandler)
    {
        $appSessionVarHandler = sprintf('%s.%s', $this->appSessionHandler, $sessionVarHandler);
        return session($appSessionVarHandler);
    }

    /**
     * 设置应用级别的session变量
     * @param $sessionVarHandler 不包含应用名称的session变量名，例如user.id，在获取session变量时，会自动加上应用名称
     * @param $sessionVarValue
     */
    protected function set_app_session($sessionVarHandler, $sessionVarValue)
    {
        $appSessionVarHandler = sprintf('%s.%s', $this->appSessionHandler, $sessionVarHandler);
        session($appSessionVarHandler, $sessionVarValue);
    }

    /**
     * 应用级别登录用户
     * @param $user
     * @param $userId
     * @param $userName
     *
     */
    protected function app_user_login($user, $userId, $userName, $headimgurl)
    {
        $this->set_app_session('user', $user);
        $this->set_app_session('user.id', $userId);
        $this->set_app_session('user.name', $userName);
        $this->set_app_session('user.headimgurl', $headimgurl);
    }

    /**
     * 应用级别注销用户
     */
    protected function app_user_logout()
    {
        $this->set_app_session('user', null);
        $this->set_app_session('user.id', null);
        $this->set_app_session('user.name', null);
        $this->set_app_session('user.headimgurl', null);
    }

    /**
     * 检查当前是否登录
     * @return bool
     */
    protected function is_user_login()
    {
        $userId = $this->get_app_session('user.id');
        if (empty($userId)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 通过请求参数中创建项目用户基础信息。该方法用在通过m.miaowutech.com获取微信用户信息后，返回本应用时创建项目用户。
     * @param $projectUserId 项目用户编号
     */
    protected function create_project_user_base_info($projectUserId)
    {
        // $projectUserModel = new \mwtech\thinkphp_app_session\model\ProjectUserModel($_REQUEST);
        // $projectUserModel->project_user_id = $projectUserId;
        // $projectUserModel->project_name = $this->appSessionHandler;
        // $projectUserModel->create_time = time();
        // $projectUserModel->last_login_time = time();
        // $projectUserModel->login_times = 1;
        // $projectUserModel->last_login_ip = $this->request->ip();
        // $projectUserModel->access_token = "";
        // $projectUserModel->expire_time = 0;
        // $projectUserModel->save();

        $saveData = [
            'project_user_id' => $projectUserId,
            'project_name' => $this->appSessionHandler,
            'last_login_time' => time(),
            'expire_time' => 0,
            'create_time' => time(),
            'login_times' => 1,
            'nickname' => Request::param('nickname', ''),
            'headimgurl' => Request::param('headimgurl', ''),
            'last_login_ip' => $this->request->ip(),
            'access_token' => "",
            'openid' => Request::param('openid', ''),
            'unionid' => Request::param('unionid', ''),
        ];
        // $saveData = array_merge($_REQUEST, $saveData);  // 数据库表里很多字段都是非空，而请求参数里未必有哪些字段，例如headimgurl，nickname, unionid等，所以采用上方的方式避免insert错误。
        $projectUserModel = ProjectUserBaseInfoModel::create($saveData);  // ThinkPHP文档里写最佳实践：使用create方法新增数据，使用saveAll批量新增数据

        return $projectUserModel;
    }
}