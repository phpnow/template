<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'PHPnow' . DIRECTORY_SEPARATOR . 'PHPnow.class.php';
$tpl = new \PHPnow;

$db = new \PDO('mysql:host=127.0.0.1;dbname=phpnow;charset=utf8', 'root', '123');
if (!empty($_GET['d']) && preg_match('/^[1-9][0-9]*$/', base64_decode($_GET['d']))) {
    $sql = "SELECT
*
FROM
now_document
LEFT JOIN now_document_article ON now_document.id = now_document_article.id WHERE now_document.id = '" . base64_decode($_GET['d']) . "' LIMIT 1";
    $tpl->list = $list = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
    if (!empty($list)) {
        $tpl->title = $list['title'] . ' - PHPnow template 模板引擎 开发手册';
        $tpl->description = $list['description'];
        $tpl->keywords = $list['keywords'];
		$sql="UPDATE `now_document` SET `view`=`view`+1 WHERE (`id`='{$list['id']}') LIMIT 1";
		$db->exec($sql);
    }else{
$tpl->title = 'PHPnow template engine 模板引擎_PHPnow_PHP模板_php框架_php程序开发_php网站建设_php网站制作';
$tpl->description = ' PHPnow是专门钻研php的一个专业团队，团队以技术为核心导向，专注php程序开发以及php框架建设，产品主要有定制和开源两大模块，开源产品有：PHPnow framework框架，PHPnow template engine 模板引擎等等，只要团队在运营，产品就会不断更新！';
$tpl->keywords = '模板引擎,PHPnow,PHP模板,php框架,php程序开发,php网站建设,php网站制作';
	}
}


$sql = "SELECT * FROM `now_category` WHERE `pid` = '1'";
$tpl->left = $left = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
$document = array();
foreach ($left as $row)
    $document[$row['id']] = $db->query("SELECT * FROM `now_document` WHERE `category_id` ='{$row['id']}'")->fetchAll(\PDO::FETCH_ASSOC);;
$tpl->document = $document;

$tpl->display('manual');