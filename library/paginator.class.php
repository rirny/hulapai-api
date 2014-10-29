<?php
/**
 * 分页类
 *
 * @package Paginator
 * @author shukyyang
 * @version $Id$
 */

/**
 * 扩展接口
 * 如需自定义分页样式算法，则需要继承此接口
 */

interface PaginatorInterface
{
    public function getPages(Paginator $paginator);
}


/**
 * 分页
 */
class Paginator
{
    // 总条数
    protected $_itemCount = 0;
    // 总页数
    protected $_pageCount = 1;
    // 每页数
    protected $_perPage = 20;
    // 当前页
    protected $_curPage = 1;
    // 显示页数
    protected $_pageRange = 10;
    // 最终分页
    protected $_pages = null;

    /**
     * 分页传入相关参数
     *
     * @param int $page 当前页
     * @param int $count 总数
     * @param int $perPage 每页数
     * @param int $pageRange 显示页数
     */
    public function __construct($page, $count, $perPage = null, $pageRange = null)
    {
        if ($perPage) {
            $this->setPerPage($perPage);
        }
        if ($pageRange) {
            $this->setPageRange($pageRange);
        }
        $this->_itemCount = (int) $count;
        $this->_curPage = ($page < 1) ? 1 : (int) $page;
        $this->_pageCount = ceil($count / $this->_perPage);
    }

    /**
     * 获取分页结果
     *
     * @param mixed $pageStyle
     * @return stdClass 分页结果类
     */
    public function getPages($pageStyle = null)
    {
        if (null === $this->_pages) {
            $this->_pages = $this->_createPages($pageStyle);
        }
        return $this->_pages;
    }

    /**
     * 获取组织好的html代码分页，样式自定义
     * 基本html分页样式，暂不作扩展，如有特殊页面显示需要可使用getPages自定义显示
     *
     * @param string $url 传入链接地址
     * @return string 返回html
     */
    public function getRender($url = '', $type=0)
    {
        // 组织url
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }
        $part = explode('?', $url);
        if (isset($part[1])) {
            $part[1] = trim(preg_replace('/&?page=\d+/i', '', $part[1]), '&');
        }
        $url = $part[0] . '?' . (empty($part[1]) ? '' : $part[1] . '&');

        // 组织html
        $html = '';
        if ($this->getPageCount() > 1) {
            $page = $this->getPages();            
			//$type == 1 && $html .= '总数：' .$this->_itemCount;
            $type == 1 && $html .= '<a href="' . $url . 'page=1">第一页</a>';
            if (isset($page->pre)) {
                $html .= '<a href="' . $url . 'page=' . $page->pre . '">上一页</a>';
            }
			if($type == 1)
			{
				foreach ($page->ranges as $p) {
					$cur = ($p == $page->curPage) ? ' class="on"' : '';
					$html .= '<a ' . $cur . ' href="' . $url . 'page=' . $p . '">' . $p . '</a>';
				}
			}
            if (isset($page->next)) {
                $html .= '<a href="' . $url . 'page=' . $page->next . '">下一页</a>';
            }
            $type == 1 && $html .= '<a href="' . $url . 'page=' . $page->pageCount . '">尾页</a>';            
        }

        return $html;
    }

    /**
     * 创建分页
     *
     * @param PaginatorInterface $pageStyle 分页计算类
     * @return object stdClass
     */
    protected function _createPages($pageStyle = null)
    {
        $pages = new stdClass();
        $pages->itemCount = $this->_itemCount;
        $pages->pageCount = $this->_pageCount;
        $pages->perPage = $this->_perPage;
        $pages->curPage = $this->_curPage;
        $pages->pageRange = $this->_pageRange;
        // 上一页
        if ($pages->curPage > 1) {
            $pages->pre = $this->_curPage - 1;
        }
        // 下一页
        if ($pages->curPage < $this->_pageCount) {
            $pages->next = $this->_curPage + 1;
        }
        // 分布
        $pages->ranges = $this->_loadPageStyle($pageStyle);
        $pages->rangeFirst = empty($pages->ranges) ? 1 : min($pages->ranges);
        $pages->rangeLast = empty($pages->ranges) ? 1 : max($pages->ranges);
        return $pages;
    }

    /**
     * 分页基础分析
     *
     * 加载分页样式
     * 如无扩展算法，则加载默认分页算法
     * @return array
     */
    protected function _loadPageStyle($pageStyle = null)
    {
        if ($pageStyle && $pageStyle instanceof PaginatorInterface) {
            return $pageStyle->getPages($this);
        } else {
            $pageRange = $this->getPageRange();
            $pageNumber = $this->getCurPage();
            $pageCount = $this->getPageCount();
            if ($pageRange > $pageCount) {
                $pageRange = $pageCount;
            }
            $delta = ceil($pageRange / 2);
            if ($pageNumber - $delta > $pageCount - $pageRange) {
                $lowerBound = $pageCount - $pageRange + 1;
                $upperBound = $pageCount;
            } else {
                if ($pageNumber - $delta < 0) {
                    $delta = $pageNumber;
                }
                $offset = $pageNumber - $delta;
                $lowerBound = $offset + 1;
                $upperBound = $offset + $pageRange;
            }
            $pages = array();
            for ($i = $lowerBound; $i <= $upperBound; $i++) {
                $pages[] = $i;
            }
            return $pages;
        }
    }

    /**
     * 获取总条数
     * @return int
     */
    public function getItemCount()
    {
        return $this->_itemCount;
    }

    /**
     * 获取总页数
     * @return int
     */
    public function getPageCount()
    {
        return $this->_pageCount;
    }

    /**
     * 获取每页显示数
     * @return int
     */
    public function getPerPage()
    {
        return $this->_perPage;
    }

    /**
     * 获取当前页数
     * @return int
     */
    public function getCurPage()
    {
        return $this->_curPage;
    }

    /**
     * 获取每页显示页数
     * @return int
     */
    public function getPageRange()
    {
        return $this->_pageRange;
    }

    /**
     * 设置每页数
     * @param int $perPage
     */
    public function setPerPage($perPage)
    {
        $this->_perPage = (int)$perPage;
    }

    /**
     * 设置每页显示页数
     * @param int $pageRange
     */
    public function setPageRange($pageRange)
    {
        $this->_pageRange = (int)$pageRange;
    }
}