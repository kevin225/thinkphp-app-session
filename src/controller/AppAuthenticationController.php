<?php
namespace mwtech\thinkphp_app_session\controller;

class AppAuthenticationController extends AppSessionController
{
    protected $appUser;
    protected $appUserId;
    protected $appUserName;

    public function initialize()
    {
        parent::initialize();
        // 检查登录状态
        if (!$this->is_user_login()) {
            $this->error('您尚未登录');
        }
        $this->appUser = $this->get_app_session('user');
        $this->appUserId = $this->get_app_session('user.id');
        $this->appUserName = $this->get_app_session('user.name');
    }
}