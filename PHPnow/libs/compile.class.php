<?php

/**
 * PHPnow Template 模板引擎 1.0
 * @copyright	(C) 2011-2013 PHPnow
 * @license	http://www.phpnow.cn
 * @author	jiaodu QQ:1286522207
 */

namespace PHPnow;

/**
 * 模板编译类
 * @author sanliang
 */
class compile {

    public function __construct(&$content, \PHPnow\template $template, $templateFile) {
        $this->__source = $content;
        $this->__template = $template;
        $this->__templateFile = $templateFile;
        $this->__ldel = preg_quote($this->__template->__leftDelimiter);
        $this->__rdel = preg_quote($this->__template->__rightDelimiter);
        $content = preg_replace("/<\?xml(.*?)\?>/s", "##XML\\1XML##", $content);
        $this->parsePhp($content);
        $content = preg_replace_callback("/##XML(.*?)XML##/s", array($this, 'xmlSubstitution'), $content);
        $this->compileTemplate($content);
        $content = "<?php class_exists('PHPnow')?:exit;?>" . $content;
        $this->stripWhitespace($content);
    }

    private function parsePhp(&$content) {
        if (!$this->__template->__phpOff)
            $content = str_replace(array("<?", "?>"), array("&lt;?", "?&gt;"), $content);
        else
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>', $content); return $content;
    }

    private function xmlSubstitution($capture) {
        return "<?php echo '<?xml " . stripslashes($capture[1]) . " ?>'; ?>";
    }

    protected function compileTemplate(&$content) {
        $tagRegexp = str_replace(array("__PHPNOW", "PHPNOW__"), array($this->__ldel, $this->__rdel), $this->__tagList);
        $tagRegexp = "/" . implode("|", $tagRegexp) . "/";
        $content = preg_split($tagRegexp, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $this->pathReplace($content);
        $this->compileCode($content);
        return $content;
    }

    private function reducePath(&$path) {
        $path = str_replace("://", "@not_replace@", $path);
        $path = str_replace("//", "/", $path);
        $path = str_replace("@not_replace@", "://", $path);
        $path = preg_replace('/\w+\/\.\.\//', '', $path);
    }

    private function pathReplace(&$content) {
        if ($this->__template->__pathReplace) {
            $path = $this->__template->__pathUrl;
            $this->reducePath($path);
            $exp = $sub = array();
            if (in_array("img", $this->__template->__pathReplaceList)) {
                $exp = array('/<img(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i', '/<img(.*?)src=(?:")([^"]+?)#(?:")/i', '/<img(.*?)src="(.*?)"/', '/<img(.*?)src=(?:\@)([^"]+?)(?:\@)/i');
                $sub = array('<img$1src=@$2://$3@', '<img$1src=@$2@', '<img$1src="' . $path . '$2"', '<img$1src="$2"');
            } if (in_array("script", $this->__template->__pathReplaceList)) {
                $exp = array_merge($exp, array('/<script(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i', '/<script(.*?)src=(?:")([^"]+?)#(?:")/i', '/<script(.*?)src="(.*?)"/', '/<script(.*?)src=(?:\@)([^"]+?)(?:\@)/i'));
                $sub = array_merge($sub, array('<script$1src=@$2://$3@', '<script$1src=@$2@', '<script$1src="' . $path . '$2"', '<script$1src="$2"'));
            } if (in_array("link", $this->__template->__pathReplaceList)) {
                $exp = array_merge($exp, array('/<link(.*?)href=(?:")(http|https)\:\/\/([^"]+?)(?:")/i', '/<link(.*?)href=(?:")([^"]+?)#(?:")/i', '/<link(.*?)href="(.*?)"/', '/<link(.*?)href=(?:\@)([^"]+?)(?:\@)/i'));
                $sub = array_merge($sub, array('<link$1href=@$2://$3@', '<link$1href=@$2@', '<link$1href="' . $path . '$2"', '<link$1href="$2"'));
            } if (in_array("a", $this->__template->__pathReplaceList)) {
                $exp = array_merge($exp, array('/<a(.*?)href=(?:")(http\:\/\/|https\:\/\/|javascript:)([^"]+?)(?:")/i', '/<a(.*?)href="(.*?)"/', '/<a(.*?)href=(?:\@)([^"]+?)(?:\@)/i'));
                $sub = array_merge($sub, array('<a$1href=@$2$3@', '<a$1href="' . $this->__template->__baseUrl . '$2"', '<a$1href="$2"'));
            } if (in_array("input", $this->__template->__pathReplaceList)) {
                $exp = array_merge($exp, array('/<input(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i', '/<input(.*?)src=(?:")([^"]+?)#(?:")/i', '/<input(.*?)src="(.*?)"/', '/<input(.*?)src=(?:\@)([^"]+?)(?:\@)/i'));
                $sub = array_merge($sub, array('<input$1src=@$2://$3@', '<input$1src=@$2@', '<input$1src="' . $path . '$2"', '<input$1src="$2"'));
            } $content = preg_replace($exp, $sub, $content);
        }
    }

    private function compileCode(&$content) {
        $compiledCode = $open_if = $comment_is_open = $ignore_is_open = null;
        $loop_level = 0;
        while ($html = array_shift($content)) {
            if (!$comment_is_open && ( strpos($html, $this->__template->__leftDelimiter . '/ignore' . $this->__template->__rightDelimiter) !== false || strpos($html, '*' . $this->__template->__rightDelimiter) !== false ))
                $ignore_is_open = false; elseif ($ignore_is_open) {
                
            } elseif (strpos($html, $this->__template->__leftDelimiter . '/noparse' . $this->__template->__rightDelimiter) !== false)
                $comment_is_open = false; elseif ($comment_is_open)
                $compiledCode .= $html; elseif (strpos($html, $this->__template->__leftDelimiter . 'ignore' . $this->__template->__rightDelimiter) !== false || strpos($html, '{*') !== false)
                $ignore_is_open = true; elseif (strpos($html, $this->__template->__leftDelimiter . 'noparse' . $this->__template->__rightDelimiter) !== false)
                $comment_is_open = true; elseif (preg_match('/' . $this->__ldel . 'include="([^"]*)"(?: cache="([^"]*)"){0,1}' . $this->__rdel . '/', $html, $code)) {
                $include_var = $this->varReplace($code[1], $left_delimiter = null, $right_delimiter = null, $php_left_delimiter = '".', $php_right_delimiter = '."', $loop_level);
                $compiledCode .=isset($code[2]) ? '<?php $tpl = clone $this;' . '$tpl->__caching = true;' . '$tpl->__acheLifetime=' . $code[2] . ';' . '$tpl->display("' . $include_var . '");' . '?>' : '<?php $tpl = clone $this;' . '$tpl->display("' . $include_var . '");' . '?>';
            } elseif (preg_match('/' . $this->__ldel . 'config="([^"]*)"(?: file="([^"]*)"){0,1}' . $this->__rdel . '/', $html, $code)) {
                $include_var = $this->varReplace($code[1], $left_delimiter = null, $right_delimiter = null, $php_left_delimiter = '".', $php_right_delimiter = '."', $loop_level);
                $compiledCode .=isset($code[2]) ? '<?php echo $this->getConfig("' . $include_var . '","' . $code[2] . '");' . '?>' : '<?php echo $this->getConfig("' . $include_var . '");' . '?>';
            } elseif (preg_match('/' . $this->__ldel . 'loop(?: name){0,1}="\${0,1}([^"]*)"' . $this->__rdel . '/', $html, $code)) {
                $loop_level++;
                $var = $this->varReplace('$' . $code[1], $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level - 1);
                $counter = "\$counter$loop_level";
                $key = "\$key$loop_level";
                $value = "\$value$loop_level";
                $compiledCode .= "<?php $counter=-1; if( isset($var) && is_array($var) && sizeof($var) ) foreach( $var as $key => $value ){ $counter++; ?>";
            } elseif (strpos($html, $this->__template->__leftDelimiter . '/loop' . $this->__template->__rightDelimiter) !== false) {
                $counter = "\$counter$loop_level";
                $loop_level--;
                $compiledCode .= "<?php } ?>";
            } elseif (preg_match('/' . $this->__ldel . 'if(?: condition){0,1}="([^"]*)"' . $this->__rdel . '/', $html, $code)) {
                $open_if++;
                $tag = $code[0];
                $condition = $code[1];
                $this->functionCheck($tag);
                $parsed_condition = $this->varReplace($condition, $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level);
                $compiledCode .= "<?php if( $parsed_condition ){ ?>";
            } elseif (preg_match('/' . $this->__ldel . 'elseif(?: condition){0,1}="([^"]*)"' . $this->__rdel . '/', $html, $code)) {
                $tag = $code[0];
                $condition = $code[1];
                $parsed_condition = $this->varReplace($condition, $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level);
                $compiledCode .= "<?php }elseif( $parsed_condition ){ ?>";
            } elseif (strpos($html, $this->__template->__leftDelimiter . 'else' . $this->__template->__rightDelimiter) !== false) {
                $compiledCode .= '<?php }else{ ?>';
            } elseif (strpos($html, $this->__template->__leftDelimiter . '/if' . $this->__template->__rightDelimiter) !== false) {
                $open_if--;
                $compiledCode .= '<?php } ?>';
            } elseif (preg_match('/' . $this->__ldel . 'function="(\w*)(.*?)"' . $this->__rdel . '/', $html, $code)) {
                $tag = $code[0];
                $function = $code[1];
                $this->functionCheck($tag);
                if (empty($code[2]))
                    $parsed_function = $function . "()";
                else
                    $parsed_function = $function . $this->varReplace($code[2], $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level); $compiledCode .= "<?php echo $parsed_function; ?>";
            } elseif (strpos($html, $this->__template->__leftDelimiter . '$tpl_info' . $this->__template->__rightDelimiter) !== false) {
                $tag = $this->__template->__leftDelimiter . '$tpl_info' . $this->__template->__rightDelimiter;
                $compiledCode .= '<?php echo "<pre>"; print_r($this->__vars ); echo "</pre>"; ?>';
            } else {
                $html = $this->varReplace($html, $left_delimiter = '' . $this->__ldel . '', $right_delimiter = '' . $this->__rdel . '', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $loop_level, $echo = true);
                $html = $this->constReplace($html, $left_delimiter = '' . $this->__ldel . '', $right_delimiter = '' . $this->__rdel . '', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $loop_level, $echo = true);
                $compiledCode .= $this->funcReplace($html, $left_delimiter = '' . $this->__ldel . '', $right_delimiter = '' . $this->__rdel . '', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $loop_level, $echo = true);
            }
        } if ($open_if > 0)
            throw new \Exception('[' . $this->__templateFile . '] ' . $this->__template->getLang(3) . ' ( if )'); $content = $compiledCode;
        unset($compiledCode);
    }

    function constReplace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null) {
        return preg_replace('/' . $this->__ldel . '\#(\w+)\#{0,1}' . $this->__rdel . '/', $php_left_delimiter . ( $echo ? " echo " : null ) . '\\1' . $php_right_delimiter, $html);
    }

    function funcReplace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null) {
        preg_match_all('/' . '' . $this->__ldel . '\#{0,1}(\"{0,1}.*?\"{0,1})(\|\w.*?)\#{0,1}' . $this->__rdel . '' . '/', $html, $matches);
        for ($i = 0, $n = count($matches[0]); $i < $n; $i++) {
            $tag = $matches[0][$i];
            $var = $matches[1][$i];
            $extra_var = $matches[2][$i];
            $this->functionCheck($tag);
            $extra_var = $this->varReplace($extra_var, null, null, null, null, $loop_level);
            $is_init_variable = preg_match("/^(\s*?)\=[^=](.*?)$/", $extra_var);
            $function_var = ( $extra_var and $extra_var[0] == '|') ? substr($extra_var, 1) : null;
            $temp = preg_split("/\.|\[|\-\>/", $var);
            $var_name = $temp[0];
            $variable_path = substr($var, strlen($var_name));
            $variable_path = str_replace('[', '["', $variable_path);
            $variable_path = str_replace(']', '"]', $variable_path);
            $variable_path = preg_replace('/\.\$(\w+)/', '["$\\1"]', $variable_path);
            $variable_path = preg_replace('/\.(\w+)/', '["\\1"]', $variable_path);
            if ($function_var) {
                $function_var = str_replace("::", "@double_dot@", $function_var);
                if ($dot_position = strpos($function_var, ":")) {
                    $function = substr($function_var, 0, $dot_position);
                    $params = substr($function_var, $dot_position + 1);
                } else {
                    $function = str_replace("@double_dot@", "::", $function_var);
                    $params = null;
                } $function = str_replace("@double_dot@", "::", $function);
                $params = str_replace("@double_dot@", "::", $params);
            }
            else
                $function = $params = null; $php_var = $var_name . $variable_path;
            if (isset($function)) {
                if ($php_var)
                    $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null ) . ( $params ? "( $function( $php_var, $params ) )" : "$function( $php_var )" ) . $php_right_delimiter;
                else
                    $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null ) . ( $params ? "( $function( $params ) )" : "$function()" ) . $php_right_delimiter;
            }
            else
                $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null ) . $php_var . $extra_var . $php_right_delimiter; $html = str_replace($tag, $php_var, $html);
        } return $html;
    }

    private function varReplace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null) {
        if (preg_match_all('/' . $tag_left_delimiter . '\$(\w+(?:\.\${0,1}[A-Za-z0-9_]+)*(?:(?:\[\${0,1}[A-Za-z0-9_]+\])|(?:\-\>\${0,1}[A-Za-z0-9_]+))*)(.*?)' . $tag_right_delimiter . '/', $html, $matches)) {
            for ($parsed = array(), $i = 0, $n = count($matches[0]); $i < $n; $i++)
                $parsed[$matches[0][$i]] = array('var' => $matches[1][$i], 'extra_var' => $matches[2][$i]); foreach ($parsed as $tag => $array) {
                $var = $array['var'];
                $extra_var = $array['extra_var'];
                $this->functionCheck($tag);
                $extra_var = $this->varReplace($extra_var, null, null, null, null, $loop_level);
                $is_init_variable = preg_match("/^[a-z_A-Z\.\[\](\-\>)]*=[^=]*$/", $extra_var);
                $function_var = ( $extra_var and $extra_var[0] == '|') ? substr($extra_var, 1) : null;
                $temp = preg_split("/\.|\[|\-\>/", $var);
                $var_name = $temp[0];
                $variable_path = substr($var, strlen($var_name));
                $variable_path = str_replace('[', '["', $variable_path);
                $variable_path = str_replace(']', '"]', $variable_path);
                $variable_path = preg_replace('/\.(\${0,1}\w+)/', '["\\1"]', $variable_path);
                if ($is_init_variable)
                    $extra_var = "=\$this->__vars['{$var_name}']{$variable_path}" . $extra_var; if ($function_var) {
                    $function_var = str_replace("::", "@double_dot@", $function_var);
                    if (($dot_position = strpos($function_var, ":"))) {
                        $function = substr($function_var, 0, $dot_position);
                        $params = substr($function_var, $dot_position + 1);
                    } else {
                        $function = str_replace("@double_dot@", "::", $function_var);
                        $params = null;
                    } $function = str_replace("@double_dot@", "::", $function);
                    $params = str_replace("@double_dot@", "::", $params);
                }
                else
                    $function = $params = null; if ($loop_level) {
                    if ($var_name == 'key')
                        $php_var = '$key' . $loop_level; elseif ($var_name == 'value')
                        $php_var = '$value' . $loop_level . $variable_path; elseif ($var_name == 'counter')
                        $php_var = '$counter' . $loop_level;
                    else
                        $php_var = '$' . $var_name . $variable_path;
                }
                else
                    $php_var = '$' . $var_name . $variable_path; if (isset($function))
                    $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null ) . ( $params ? "( $function( $php_var, $params ) )" : "$function( $php_var )" ) . $php_right_delimiter;
                else
                    $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null ) . $php_var . $extra_var . $php_right_delimiter; $html = str_replace($tag, $php_var, $html);
            }
        } return $html;
    }

    private function functionCheck($code) {
        $preg = '#(\W|\s)' . implode('(\W|\s)|(\W|\s)', $this->__template->__blackList) . '(\W|\s)#';
        if (count($this->__template->__blackList) && preg_match($preg, $code, $match)) {
            throw new \Exception('[' . $this->__templateFile . '] ' . $this->__template->getLang(2) . ' (' . $code . ' )');
        }
    }

    private function stripWhitespace(&$content) {
        $stripStr = '';
        $tokens = token_get_all($content);
        $last_space = false;
        for ($i = 0, $j = count($tokens); $i < $j; $i++) {
            if (is_string($tokens[$i])) {
                $last_space = false;
                $stripStr .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    case T_COMMENT: case T_DOC_COMMENT: break;
                    case T_WHITESPACE: if (!$last_space) {
                            $stripStr .= ' ';
                            $last_space = true;
                        } break;
                    default: $last_space = false;
                        $stripStr .= $tokens[$i][1];
                }
            }
        } return $stripStr;
    }

    public $__template, $__templateFile, $__ldel, $__rdel, $__source, $__tagList = array('loop' => '(__PHPNOWloop(?: name){0,1}="\${0,1}[^"]*"PHPNOW__)', 'loop_close' => '(__PHPNOW\/loopPHPNOW__)', 'if' => '(__PHPNOWif(?: condition){0,1}="[^"]*"PHPNOW__)', 'elseif' => '(__PHPNOWelseif(?: condition){0,1}="[^"]*"PHPNOW__)', 'else' => '(__PHPNOWelsePHPNOW__)', 'if_close' => '(__PHPNOW\/ifPHPNOW__)', 'function' => '(__PHPNOWfunction="[^"]*"PHPNOW__)', 'noparse' => '(__PHPNOWnoparsePHPNOW__)', 'noparse_close' => '(__PHPNOW\/noparsePHPNOW__)', 'ignore' => '(__PHPNOWignorePHPNOW__|__PHPNOW\*)', 'ignore_close' => '(__PHPNOW\/ignorePHPNOW__|\*PHPNOW__)', 'include' => '(__PHPNOWinclude="[^"]*"(?: cache="[^"]*")?PHPNOW__)', 'config' => '(__PHPNOWconfig="[^"]*"(?: file="[^"]*")?PHPNOW__)', 'tpl_info' => '(__PHPNOW\$tpl_infoPHPNOW__)', 'function' => '(__PHPNOWfunction="(\w*?)(?:.*?)"PHPNOW__)');

}