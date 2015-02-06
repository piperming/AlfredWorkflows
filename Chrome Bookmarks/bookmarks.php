<?php
define('USER', exec('whoami'));
define('CHROME_BOOKMARKS', '/Users/' . USER . '/Library/Application Support/Google/Chrome/Default/Bookmarks');
define('BOOKMARKS', 'bookmarks');
define('HASH_MARK', 'hash');

include 'workflows.php';

class Bookmarks {

    /**
     * 处理标签内容
     */
    private static function dealBookmarks($bookmarks_array) {
        $content = '';
        foreach ($bookmarks_array as $bookmarks) {
            if (isset($bookmarks['name']) && isset($bookmarks['url'])) {
                $name = addslashes($bookmarks['name']);
                $url = addslashes($bookmarks['url']);
                $content .= "'{$name}'=>'$url',";
            }
            if (isset($bookmarks['children'])) {
                $content .= self::dealBookmarks($bookmarks['children']);
            }
        }
        return $content;
    }

    /**
     * 读取原始标签
     */
    private static function readBookmarks() {
        $bookmarks_content = file_get_contents(CHROME_BOOKMARKS, 'r');
        $hash = md5($bookmarks_content);
        $orgin_hash = @file_get_contents(HASH_MARK, 'r');
        if ($hash != $orgin_hash) {
            $bookmarks_array = json_decode($bookmarks_content, true);
            $bookmarks_array = $bookmarks_array['roots'];
            $content = ' <?php return array(' . self::dealBookmarks($bookmarks_array) . ');';
            file_put_contents(BOOKMARKS, $content);
            file_put_contents(HASH_MARK, $hash);
        }
    }

    /**
     * 查询标签
     * @param $keywords
     * @return array
     */
    private static function searchBookmarks($keywords) {
        $bookmarks = include(BOOKMARKS);
        $search_result = array();
        foreach ($bookmarks as $name => $url) {
            $haystack = $name . trim($url, 'http://');
            if (strpos(strtolower($haystack), strtolower($keywords)) !== false) {
                $search_result[$name] = $url;
            }
        }
        return $search_result;
    }

    /**
     * 展示查询结构
     * @param $search_result
     */
    private static function showResult($search_result) {
        $Workflows = new Workflows();
        $i = 0;
        foreach ($search_result as $name => $url) {
            $title = $name;
            $sub = $url;
            $Workflows->result($i, $url, $title, $sub, 'icon.png');
            $i++;
        }
        echo $Workflows->toxml();
    }

    /**
     * Run
     * @param $keyword
     */
    public static function run($keyword) {
        $keyword = trim($keyword);
        self::readBookmarks();
        $search_result = self::searchBookmarks($keyword);
        self::showResult($search_result);
    }
}

