<?php
namespace mwtech\thinkphp_app_session\controller;

use think\facade\Request;
use cmf\controller\HomeBaseController;
use mwtech\thinkphp_app_session\model\IUserModel;
use mwtech\thinkphp_app_session\model\ProjectUserBaseInfoModel;
use think\facade\Config;
use think\facade\Route;
use think\facade\Log;
use think\facade\Event;
use think\facade\Db;

class AppSessionController extends HomeBaseController
{
    protected $appSessionHandler;  // 应用级的session句柄

    protected IUserModel $userModel;  // 应用的用户模型，有应用的Controller注入

    protected function initialize()
    {
        parent::initialize();
        $this->appSessionHandler = app('http')->getName();  // 使用ThinkPHP的应用名称
        
        // 以下系统路径可作为应用开发的参考
        // $thisDirectory = __DIR__;
        // Log::record('在AppSessionController的initialize方法中测试各个系统变量：');
        // Log::record('当前程序目录是：' . $thisDirectory);        // /home/wwwroot/miaoling.miaowutech.com/vendor/mwtech/thinkphp-app-session/src/controller
        // Log::record('当前应用目录是app_path()：' . app_path());  // /home/wwwroot/miaoling.miaowutech.com/app/
        // Log::record('应用基础目录this->app->getRootPath():'. $this->app->getRootPath());  // /home/wwwroot/miaoling.miaowutech.com/
        // Log::record('应用基础目录base_path():'. base_path());   // /home/wwwroot/miaoling.miaowutech.com/app/
        // Log::record('应用配置目录this->app->getConfigPath():'. $this->app->getConfigPath());  // /home/wwwroot/miaoling.miaowutech.com/config/
        // Log::record('应用配置目录config_path():'. config_path());                             // /home/wwwroot/miaoling.miaowutech.com/config/
        // Log::record('web根目录public_path():'. public_path());  // /home/wwwroot/miaoling.miaowutech.com/public/
        // Log::record('应用根目录root_path():' . root_path());    // /home/wwwroot/miaoling.miaowutech.com/
        // Log::record('应用运行时目录runtime_path():' . runtime_path());  // /home/wwwroot/miaoling.miaowutech.com/data/runtime/
        // Log::record('当前应用的目录名app(\'http\')->getName()：' . $this->appSessionHandler);  // 应用的目录名，例如project_cgb_202410_mwtest或者project_xijiu_202411_mwtest
        
        // 加载项目内的配置/config/project.php
        $projectConfigPath = app_path() . DIRECTORY_SEPARATOR . $this->appSessionHandler . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'project.php';
        Config::load($projectConfigPath, 'project');
        
        // 想通过应用级的配置来加载事件、监听器的配置，结果ThinkCMF是不支持应用级配置的，以下方法也只能作为通用型配置使用吧
        // $appEventConfigPath = app_path() . DIRECTORY_SEPARATOR . $this->appSessionHandler . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'event.php';
        // Log::record('当前应用的事件配置文件路径：' . $appEventConfigPath);
        // Config::load($appEventConfigPath);
        // 动态绑定用户注册事件并注册其监听器
        $userRegisterEventClassPath = 'app\\' . $this->appSessionHandler . '\\event\\UserRegisterEvent';
        Event::bind(['userRegister' => $userRegisterEventClassPath]);
        $userRegisterListenerClassPath = 'app\\' . $this->appSessionHandler . '\\listener\\UserRegisterListener';
        Event::listen('userRegister', $userRegisterListenerClassPath);
        // 动态绑定用户登录事件并注册其监听器
        $userLoginEventClassPath = 'app\\' . $this->appSessionHandler . '\\event\\UserLoginEvent';
        Event::bind(['userLogin' => $userLoginEventClassPath]);
        $userLoginListenerClassPath = 'app\\' . $this->appSessionHandler . '\\listener\\UserLoginListener';
        Event::listen('userLogin', $userLoginListenerClassPath);

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
        $this->set_app_session('user', $user);  // 这是一个数组，返回了id（项目user的）、name（BaseInfo的）和headimgurl（BaseInfo的）这三个字段，为什么，暂时不得而知，建议该session变量暂时不要使用
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
            'share_code' => md5($this->appSessionHandler . Request::param('openid', ''))
        ];
        $link = Request::param('link', '');
        if(!empty($link)) {
            $shareFromProjectUser = ProjectUserBaseInfoModel::where('share_code', $link)->find();
            if ($shareFromProjectUser) {
                $saveData['invite_from_user_id'] = $shareFromProjectUser->id;
            }
        }
        // $saveData = array_merge($_REQUEST, $saveData);  // 数据库表里很多字段都是非空，而请求参数里未必有哪些字段，例如headimgurl，nickname, unionid等，所以采用上方的方式避免insert错误。
        $projectUserModel = ProjectUserBaseInfoModel::create($saveData);  // ThinkPHP文档里写最佳实践：使用create方法新增数据，使用saveAll批量新增数据
        event('userRegister', $projectUserModel);  // 触发项目内的用户注册事件，如果有

        return $projectUserModel;
    }

    /**
     * 获取应用级配置，将/config/app.php的project_config项
     * @param $configName
     * @param string $defaultValue
     * @return string
     */
    protected function get_app_config($configName, $defaultValue = "")
    {
        if (empty($configName)) {
            return "";
        }
        $configHandler = sprintf('app.project_config.%s.%s', $this->appSessionHandler, $configName);
        return Config::get($configHandler, $defaultValue);
    }

    /**
     * 检查是否登录，登录则返回登录用户信息，否则返回登录授权地址
     * url: /project_cgb_202410_mwtest/index/checkLogin
     * @param $scope 登录授权范围，snsapi_userinfo（默认）：获取用户昵称和头像，snsapi_base：获取用户openid
     * @param $link 微信授权登录后返回本应用时的参数，请求该接口时，前端页面的参数link的值，通常用作标识该链接是哪个用户分享出去的，等登录成功后，提交到后端，以便记录分享引流情况
     * @param $type 微信授权登录后返回本应用时的参数，请求该接口时，前端页面的参数type的值，通常用作标识应该显示哪个页面，参数由前端自行定义以及处理，后端只负责传递
     * @param $extendParam 扩展参数，可以和前端约定好需要什么名称的参数，然后在/项目目录/config/project.php的'mainpage_extend_param'配置项里进行配置
     */
    public function checkLogin()
    {
        if ($this->is_user_login()) {
            $userInfo = [
                'headimg' => $this->get_app_session('user.headimgurl'),
                'nickname' => $this->get_app_session('user.name'),
                'id' => $this->get_app_session('user.id')
            ];
            return $this->success('已登录', null, json_encode($userInfo));
        } else {
            // 构建喵呜微信登录地址，也可以不管，前端自行拼凑就行
            $authenticationBaseUrl = Config::get('app.miaowu_weixin_login_url');
            $scope = Request::param('scope', 'snsapi_userinfo');  // 获取微信登录授权范围，snsapi_userinfo：获取用户昵称和头像，snsapi_base：获取用户openid
            $token = Config::get('app.miaowu_weixin_api_token');  // 喵呜微信api token，详见对应接口说明
            // $link = Request::param('link', '');  // 微信授权登录后返回本应用时的参数，请求该接口时，前端页面的参数link的值，通常用作标识该链接是哪个用户分享出去的，等登录成功后，提交到后端，以便记录分享引流情况
            // $type = Request::param('type', '');  // 微信授权登录后返回本应用时的参数，请求该接口时，前端页面的参数type的值，通常用作标识应该显示哪个页面，参数由前端自行定义以及处理，后端只负责传递
            // $redirectUriParam = [
            //     'link' => $link,
            //     'type' => $type
            // ];
            $redirectUriParam = $this->create_callback_url_paramvalues();
            $redirectUri = url('getLogin', $redirectUriParam, false, true);  // 喵呜微信api执行完之后跳转回本应用的链接（含参数）
            $forceHttps = Config::get('app.force_https', false);
            if ($forceHttps) {
                $redirectUri = Route::buildUrl('getLogin', $redirectUriParam)->domain(true)->suffix('')->https(true)->build();  // 因为CDN回源是80端口，url方法得到的结果是http协议的，所以这里改用Route::buildUrl方法，并且在链式操作里加上https(true)方法
            }
            $authenticationUrl = sprintf("%s?scope=%s&token=%s&redirect_uri=%s", $authenticationBaseUrl, $scope, $token, urlencode($redirectUri));
            return $this->error('未登录', $authenticationUrl);
        }
    }

    /**
     * 获取喵呜登录服务返回的微信用户信息进行登录操作，包括记录微信用户、登录本应用和构建跳转回项目前端的url（含参数）。如果不满足需求，子类可以重写该方法。参数包括发起授权登录时指定的redirect_uri（含参数），以及微信授权用户信息后的openid、nickname、headimgurl等。
     */
    public function getLogin()
    {
        $callBackUrl = $this->create_callback_url();

        // 如果已经登录，就直接跳转
        if ($this->is_user_login()) {
            return redirect($callBackUrl);
        }

        $miaowuApiToken = Config::get('app.miaowu_weixin_api_token');
        if ($_GET['sign'] && $_GET['sign'] == md5($_GET['openid'] . $miaowuApiToken . 'welcometomw')) {
            $projectUserBaseInfo = ProjectUserBaseInfoModel::where('openid', $_GET['openid'])
                                                            ->where('project_name', $this->appSessionHandler)
                                                            ->find();
            if ($projectUserBaseInfo) {  // 项目里有这个openid的用户
                // $projectUser = UserModel::where('id', $projectUserBaseInfo->project_user_id)->find();
                $projectUser = $this->userModel->findById($projectUserBaseInfo->project_user_id);
                $this->app_user_login($projectUser, $projectUser->id, $projectUserBaseInfo->nickname, $projectUserBaseInfo->headimgurl);
                $userData = [
                    'last_login_ip'   => $this->request->ip(),
                    'last_login_time' => time(),
                    'login_times'     => Db::raw('login_times+1')
                ];
                $projectUserBaseInfo->data($userData)->save();
                // $projectUserBaseInfo->last_login_ip = $this->request->ip();
                // $projectUserBaseInfo->last_login_time = time();
                // // $projectUserBaseInfo->login_times = $projectUserBaseInfo->login_times + 1;
                // $projectUserBaseInfo->login_times = Db::raw('login_times+1');
                // $projectUserBaseInfo->save();
            } else {
                // 创建项目用户
                $projectUser = $this->userModel->initialUser();  // 见对应应用的UserModel->initialUser()
                $projectUserBaseInfo = $this->create_project_user_base_info($projectUser->id);
                $this->app_user_login($projectUser, $projectUser->id, $projectUserBaseInfo->nickname, $projectUserBaseInfo->headimgurl);
            }
            event('userLogin', $projectUser);  // 这里的projectUser是项目的用户模型，不是base info模型。此处调用$projectUser本来不符合常规的变量作用范围特性，理论上在上方判断之前先定义一个变量会更好看，但这里是php，呵呵
        }
        return redirect($callBackUrl);
    }

    /**
     * 构建登录后回跳的url，参数包括默认的link和type，还可以通过配置扩展其他参数
     * @return string 例如https://cgb.miaowutech.com/202410mwtest/index.html?link=ueoqueyaxf&type=index&extendParam1=xxx&extendParam2=yyy。其中link和type是默认就有的参数，extendParam1和extendParam2是可配置的，见/项目目录/config/project.php的mainpage_extend_param配置项
     */
    private function create_callback_url()
    {
        // $callBackUrl = $this->get_app_config('project_mainpage', 'https://www.miaowutech.com/');  // 已废弃，采用项目内配置，将/项目目录/config/project.php
        $callBackUrl = Config::get('project.mainpage', 'https://www.miaowutech.com/');
        // Log::debug('通过项目内配置得到的callBackUrl是：' . $callBackUrl);
        $callBackUrlParamValues = $this->create_callback_url_paramvalues();
        $callBackUrlQuerystring = http_build_query($callBackUrlParamValues);  // 这里得到一个paramKey1=paramValue1&paramKey2=paramValue2字符串
        // 如果$callBackUrlQuerystring不为空，则将$callBackUrlQuerystring拼接到$callBackUrl后面。如果$callBackUrl已经有queryString，则先拼接一个&符号，如果没有querystring，则先拼接一个?符号
        $callBackUrl = $callBackUrl . (empty($callBackUrlQuerystring) ? '' : (strpos($callBackUrl, '?') === false ? '?' : '&') . $callBackUrlQuerystring);
        return $callBackUrl;
    }

    /**
     * 构建最终返回项目前端首页时的参数数组
     * @return array 例如['link'=>'ueoqueyaxf','type'=>'index','extendParam1'=>'xxx','extendParam2'=>'yyy']。其中link和type是默认就有的参数，extendParam1和extendParam2是可配置的，见/项目目录/config/project.php的mainpage_extend_param配置项
     */
    private function create_callback_url_paramvalues()
    {
        // 先把内置参数link和type加上
        $callBackUrlParam = ['link','type'];
        $callBackUrlExtendParam = Config::get('project.mainpage_extend_param', []);
        // 如果$callBackUrlExtendParam不为空，则合并到$callBackUrlParam中
        if (!empty($callBackUrlExtendParam)) {
            $callBackUrlParam = array_merge($callBackUrlParam, $callBackUrlExtendParam);
        }
        // 去除掉$callBackUrlParam里重复的参数名
        $callBackUrlParam = array_unique($callBackUrlParam);
        // 通过$callBackUrlParam数组里的参数名，使用Request:param获取参数值，构建成[参数名=>参数值]的数组
        $callBackUrlParamValues = [];
        foreach ($callBackUrlParam as $paramName) {
            $paramValue = Request::param($paramName, '');
            if (!empty($paramValue)) {
                $callBackUrlParamValues[$paramName] =  $paramValue;
            }
        }
        return $callBackUrlParamValues;
    }
}