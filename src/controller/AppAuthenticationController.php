<?php
namespace mwtech\thinkphp_app_session\controller;

class AppAuthenticationController extends AppSessionController
{
    protected $appUser;
    protected $appUserId;
    protected $appUserName;

    protected function initialize()
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

    /**
     * 获取当前登录用户的分享码
     * @return string
     */
    public function get_share_code() {
        // dump($this->appUser);
        // dump($this->appUserId);
        // dump($this->appUserName);
        $returnData = [
            'appUser' => $this->appUser,
            'appUserId' => $this->appUserId,
            'appUserName' => $this->appUserName,
        ];
        if (!empty($this->appUserId)) {
            $projectUser = $this->userModel->findById($this->appUserId);
            $returnData['share_code'] = $projectUser->projectUserBaseInfo->share_code;
            return $this->success('操作完成', null, $returnData);
        }
        return $this->error('操作失败');
    }
}