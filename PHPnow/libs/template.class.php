<?php

/**
 * PHPnow Template 模板引擎 1.0
 * @copyright	(C) 2011-2013 PHPnow
 * @license	http://www.phpnow.cn
 * @author	jiaodu QQ:1286522207
 */

namespace PHPnow;

/**
 * 引擎核心
 * @author sanliang
 */
class template {

    const VERSION = '1.0.0';
    const DS = DIRECTORY_SEPARATOR;
    const CHARSET = 'utf-8';
    const CHARLIST = '\\//';
    const TEMPLATECACHESUFFIX = '.cache.php';
    const TEMPLATECOMPILESUFFIX = '.compile.php';

//模板目录
    public $__templateDir;
//模板后缀
    public $__templateSuffix = '.html';
//编译目录
    public $__compileDir;
//是否调试，调试时不检查缓存
    public $__debugging = false;
//是否启用路径替换功能 images (<img src="...">), stylesheet (<link href="...">), script (<script src="...">) and link (<a href="...">)
    public $__pathReplace = true;
//路径替换名单
    public $__pathReplaceList = array('a', 'img', 'link', 'script', 'input');
//路径URL
    public $__pathUrl = '/Style/';
//网址相对路径
    public $__baseUrl = null;
//模板标签禁用黑名单
    public $__blackList = array('\$this', 'raintpl::', 'self::', '_SESSION', '_SERVER', '_ENV', 'eval', 'exec', 'unlink', 'rmdir');
//缓存目录
    public $__cacheDir;
//缓存类型
    public $__cacheType = 'File';
//缓存ID
    public $__cacheId = '';
//配制目录
    public $__configDir;
//配制文件后缀
    public $__configSuffix = '.ini';
//语言包目录
    public $__langDir;
//模板解析左分隔符
    public $__leftDelimiter = '{';
//模板解析右分隔符
    public $__rightDelimiter = '}';
//模板变量
    public $__vars = array();
//是否运行模板内插入PHP代码
    public $__phpOff = false;
//限制模板文件的大小 单位 M
    public $__templateMax = 1;
//模板缓存过期时间,为-1，则设置缓存永不过期,0可以让缓存每次都重新生成
    public $__acheLifetime = 0;
//是否自动开启缓存
    public $__autoCaching = true;
//是否启动缓存
    public $__caching = false;
//在编译目录 和 缓存目录下面创建子目录
    public $__useSubDirs = true;
//去除html空格与换行
    public $__stripSpace = true;
//信息提示语言 默认中文
    public $__lang = 'zh-cn';
    private $__runtimeStart;
    private $__runtimeEnd;
    private $__isCaching = false;
    private $__cachingTemplate;
    static $__config = array();

    function __construct() {
        mb_internal_encoding(self::CHARSET);
        $this->init();
    }

    public function init() {
        $this->__templateDir = dirname(dirname(__DIR__)) . self::DS . 'Tpl';
        $this->__configDir = dirname(dirname(__DIR__)) . self::DS . 'Config';
        $this->__compileDir = dirname(dirname(__DIR__)) . self::DS . 'Runtime' . self::DS . 'compile';
        $this->__cacheDir = dirname(dirname(__DIR__)) . self::DS . 'Runtime' . self::DS . 'cache';
    }

    /**
     * 配制模板目录
     * @access public
     * @param string $dir 绝对目录
     * @return \PHPnow\template
     */
    public function setTemplateDir($dir) {
        if (!empty($dir))
            $this->__templateDir = array_merge($this->__templateDir, (array) $dir);
        return $this;
    }

    /**
     * 配制配制目录
     * @access public
     * @param string $dir 绝对目录
     * @return \PHPnow\template
     */
    public function setConfigDir($dir) {
        if (!empty($dir))
            $this->__configDir = array_merge($this->__configDir, (array) $dir);
        return $this;
    }

    /**
     * 配制编译目录
     * @access public
     * @param string $dir 绝对目录
     * @return \PHPnow\template
     */
    public function setCompileDir($dir) {
        if (!empty($dir))
            $this->__compileDir = $dir;
        return $this;
    }

    /**
     * 调试模式
     * @param boolean $cont
     * @return \PHPnow\template
     */
    public function debugging($cont = true) {
        $this->__debugging = (boolean) $cont;
        return $this;
    }

    /**
     * 配制缓存目录
     * @access public
     * @param string $dir 绝对目录
     * @return \PHPnow\template
     */
    public function setCacheDir($dir) {
        if (!empty($dir))
            $this->__cacheDir = $dir;
        return $this;
    }

    /**
     * 设置标签黑名单
     * @param string $string
     * @return \PHPnow\template
     */
    public function setblackList($string) {
        $this->__blackList = $string;
        return $this;
    }

    /**
     * 注入变量
     * @access public
     * @param string|null $var 变量名称
     * @param * $value 值
     */
    public function assign($var, $value = null) {
        is_array($var) ? ($this->__vars = array_merge($this->__vars, $var)) : $this->__vars[$var] = $value;
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法，
     * @access public
     * @param string $template 指定要调用的模板文件
     * @param string $suffix 模板后缀
     * @return void
     */
    public function display($template = null) {
        echo $this->fetch($template);
    }

    /**
     * 检测缓存是否存在
     * @access public
     * @param string $template 指定要调用的模板文件
     * @return boolean
     */
    public function isCached($template = null, $cacheId = null) {
        if ($this->__debugging)
            return false;
        if ($cacheId !== null)
            $this->__cacheId = $cacheId;
        if ($this->__autoCaching)
            $this->__caching = true;
        $this->__cachingTemplate? : ($this->__cachingTemplate = $this->getCacheFile($template));
        if (!file_exists($this->__cachingTemplate))
            $this->__isCaching = false;

        else {
            $savet = filemtime($template);
            $fromt = filemtime($this->__cachingTemplate);
            if ($savet > $fromt)
                $this->__isCaching = false;
            elseif ($this->__acheLifetime == -1)
                $this->__isCaching = true;
            elseif ($fromt + $this->__acheLifetime < time())
                $this->__isCaching = false;
            else
                $this->__isCaching = true;
        }
        return $this->__isCaching;
    }

    /**
     *  获取输出页面内容
     * @access public
     * @param string $template 指定要调用的模板文件
     * @param string $cacheId 缓存缓存识别ID
     * @param string $suffix 模板后缀
     * @return string
     */
    public function fetch($template = null) {
        $this->getTemplateFile($template);
        $this->__runtimeStart = microtime(true);
        ob_start();
        ob_implicit_flush(0);
        extract($this->__vars, EXTR_OVERWRITE);
        if (!$this->__debugging && $this->__caching) {
            $cachingTemplate = $this->__cachingTemplate? : $this->getCacheFile($template);
            include (($this->__isCaching === true || $this->isCached($template)) ? $cachingTemplate : $this->compile($template));
        }
        else
            include ($this->compile($template));
        $content = ob_get_clean();
        $this->__runtimeEnd = microtime(true);
        if ($this->__stripSpace)
            $content = preg_replace(array('~>\s+<~', '~>(\s+\n|\r)~'), array('><', '>'), $content);
        if (!$this->__debugging && $this->__caching && !empty($cachingTemplate)) {
            $this->mkRecur(dirname($cachingTemplate));
            file_put_contents($cachingTemplate, $content);
        }
        return $content;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $template 指定要调用的模板文件
     * @return boolean
     */
    public function deleteCached($template) {
        $filename = $this->getCacheFile($template);
        if (file_exists($filename))
            return @unlink($filename);
        return false;
    }

    /**
     * 删除编译
     * @access public
     * @param string $template 指定要调用的模板文件
     * @return boolean
     */
    public function deleteCompile($template) {
        $filename = $this->getCompileFile($template);
        if (file_exists($filename))
            return @unlink($filename);
        return false;
    }

    /**
     * 清空缓存
     * @access public
     * @return boolean
     */
    public function flushCached() {
        return $this->clearRecur($this->__cacheDir, true);
    }

    /**
     * 清空编译
     * @access public
     * @return boolean
     */
    public function flushCompile() {
        return $this->clearRecur($this->__compileDir, true);
    }

    /**
     * 编译
     * @param string $template
     */
    private function compile($template) {
        if (!file_exists($template))
            throw new \Exception($this->getLang(0));
        if (filesize($template) > $this->__templateMax * 1024 * 1024)
            throw new \Exception('[' . $template . '] ' . $this->getLang(1) . ' (' . $this->__templateMax . ' M)');
        $compile = $this->getCompileFile($template);
        if (file_exists($compile)) {
            $savet = filemtime($template);
            $fromt = filemtime($compile);
            if ($savet <= $fromt)
                return $compile;
        }

        $content = file_get_contents($template);
        require_once (__DIR__ . self::DS . 'compile.class.php');
        new compile($content, $this, $template);


        $this->mkRecur(dirname($compile));
        file_put_contents($compile, $content);
        return $compile;
    }

    /**
     *  获取模板配制
     * @access public
     * @param string $key
     * @param string $file
     * @return array
     */
    public function getConfig($key, $file = 'global') {
        if (!isset(self::$__config[$file])) {
            $config = rtrim($this->__configDir, self::CHARLIST) . self::DS . $file . $this->__configSuffix;
            if (!file_exists($config))
                throw new \Exception('[' . $config . '] ' . $this->getLang(4));
            self::$__config[$file] = parse_ini_file($config, false);
        }
        if (isset(self::$__config[$file][$key]))
            return self::$__config[$file][$key];
        throw new \Exception('[' . $config . '] ' . $this->getLang(5) . ' [ ' . $key . ' ] ');
    }

    public function retRunTime($dec = 6) {
        return number_format(($this->__runtimeEnd - $this->__runtimeStart), $dec) . 's';
    }

    /**
     *  获取模板缓存文件
     * @access public
     * @param string $template 模板
     * @return string
     */
    public function getCacheFile($template) {
        $this->getTemplateFile($template);
        $this->resolveTemplateFile($template, $this->__cacheId);
        return rtrim($this->__cacheDir, self::CHARLIST) . self::DS . rtrim($template, self::CHARLIST) . self::TEMPLATECACHESUFFIX;
    }

    /**
     *  获取模板编译文件
     * @access public
     * @param string $template  模板
     * @return string
     */
    public function getCompileFile($template) {
        $this->getTemplateFile($template);
        $this->resolveTemplateFile($template);
        return rtrim($this->__compileDir, self::CHARLIST) . self::DS . rtrim($template, self::CHARLIST) . self::TEMPLATECOMPILESUFFIX;
    }

    /**
     *  获取模板文件
     * @access public
     * @param string $template  模板
     * @return string
     */
    public function getTemplateFile(&$template) {
        $template = str_replace(self:: CHARLIST, self::DS, $template);
        if (file_exists($template))
            return $template;
        foreach ((array) $this->__templateDir as $row) {
            $t = rtrim($row, self::CHARLIST) . self::DS . $template . $this->__templateSuffix;
            if (file_exists($t)) {
                return $template = $t;
            }
        }
        return $template = null;
    }

    /**
     * 获取信息提示
     * @access public
     * @param int $key
     * @return string
     */
    public function getLang($key) {
        static $__Lang = array();
        if (empty($__Lang)) {
            $filtername = dirname(__DIR__) . self::DS . 'Lang' . self::DS . $this->__lang . '.php';
            if (!file_exists($filtername)) {
                header('Content-Type:text/html; charset=utf-8');
                die('语言包加载失败');
            }
            $__Lang = include $filtername;
        }
        return isset($__Lang[$key]) ? $__Lang[$key] : null;
    }

    /**
     * 解释引擎文件
     * @access public
     * @param string $template
     * @param string $cacheId
     * @return string
     */
    public function resolveTemplateFile(&$template, $cacheId = '') {
        $template = md5($template . $cacheId);
        if ($this->__useSubDirs) {
            $dir = '';
            for ($i = 0; $i < 6; $i++)
                $dir .= $template{$i} . $template{++$i} . self::DS;
            $template = $dir . $template;
        }
        return $template;
    }

    /**
     * 递归的创建目录
     * @param string $path 目录路径
     * @param int $permissions 权限
     * @return boolean
     */
    public function mkRecur($path, $permissions = 0777) {
        if (is_dir($path))
            return true;
        $_path = dirname($path);
        if ($_path !== $path)
            $this->mkRecur($_path, $permissions);
        return @mkdir($path, $permissions);
    }

    /**
     * 递归的删除目录
     * @param string $dir 目录
     * @param Boolean $delFolder 是否删除目录
     */
    public function clearRecur($dir, $delFolder = false) {
        if (!is_dir($dir))
            return false;
        if (!$handle = @opendir($dir))
            return false;
        while (false !== ($file = readdir($handle))) {
            if ('.' === $file || '..' === $file)
                continue;
            $_path = $dir . self::DS . $file;
            if (is_dir($_path)) {
                $this->clearRecur($_path, $delFolder);
            } elseif (is_file($_path))
                @unlink($_path);
        }
        $delFolder && @rmdir($dir);
        @closedir($handle);
        return true;
    }

}