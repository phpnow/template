<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'PHPnow' . DIRECTORY_SEPARATOR . 'PHPnow.class.php';

$tpl = new \PHPnow;
$template='index';
if(!$tpl->isCached($template)){
$tpl->title = 'PHPnow template engine 模板引擎_PHPnow_PHP模板_php框架_php程序开发_php网站建设_php网站制作';
$tpl->description = ' PHPnow是专门钻研php的一个专业团队，团队以技术为核心导向，专注php程序开发以及php框架建设，产品主要有定制和开源两大模块，开源产品有：PHPnow framework框架，PHPnow template engine 模板引擎等等，只要团队在运营，产品就会不断更新！';
$tpl->keywords = '模板引擎,PHPnow,PHP模板,php框架,php程序开发,php网站建设,php网站制作';
$tpl->version = 'v1.0.0';
$tpl->navigation = array(
    '//github.com/phpnow/template' =>array('icon-code','源码') ,
    'manual.php' =>array('icon-book', '手册') ,
    '//github.com/phpnow/template/issues' =>array('icon-bug', '反馈') ,
    '//github.com/phpnow/template/releases' =>array('icon-random', '历史') ,
);
}
$tpl->display($template);