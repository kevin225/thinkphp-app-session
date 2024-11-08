<?php
namespace mwtech\thinkphp_app_session\controller;

use cmf\controller\HomeBaseController;

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
    protected function app_user_login($user, $userId, $userName)
    {
        $this->set_app_session('user', $user);
        $this->set_app_session('user.id', $userId);
        $this->set_app_session('user.name', $userName);
    }
}