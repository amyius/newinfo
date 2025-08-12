<?php

namespace app\controller;

use app\BaseController;
use think\facade\Log;
use app\common\Helper;
use think\facade\Db;
use Parsedown;

class Index extends BaseController
{
    public function index()
    {
        $helper = new \app\common\Helper;
        $websiteId = $helper->getWebsiteId();
        $domain    = $this->request->domain();

        $banner = Db::name('lt_con2website')
            ->alias('c2w')
            ->join('lt_content c', 'c2w.cw_id = c.con_id')
            ->where(['c2w.cw_website_id' => $websiteId, 'c2w.is_banner' => 1])
            ->field('c.*')
            ->order('c.updatetime DESC')
            ->limit(3)
            ->select()
            ->toArray();
        $popolar = Db::name('lt_con2website')
            ->alias('c2w')
            ->join('lt_content c', 'c2w.cw_id = c.con_id')
            ->where(['c2w.cw_website_id' => $websiteId, 'c2w.is_hot' => 1])
            ->field('c.con_id,c.title,c.cover,c.updatetime')
            ->order('c.updatetime DESC')
            ->limit(8)
            ->select()
            ->toArray();
        $menu = $helper->menuSet($websiteId);
        $relatedInfo = $this->relatedInfo($websiteId, $this->request->domain());
        $latest = $relatedInfo['latest'];
        $dynamics = $relatedInfo['dynamics'];
        $content = $relatedInfo['content'];
        $middlecarousel = Db::name('lt_con2website')
            ->alias('c2w')
            ->join('lt_content c', 'c2w.cw_id = c.con_id')
            ->where(['c2w.cw_website_id' => $websiteId])
            ->field('c.*')
            ->orderRaw('RAND()')
            ->limit(15)
            ->select()
            ->toArray();
        $bottomnav = Db::name('lt_menu')
            ->where('menu_pid', 0)
            ->where('menu_website_id', $websiteId)
            ->where('menu_istop', 2)
            ->select()
            ->toArray();

        foreach ($bottomnav as &$menus) {
            $menus['contents'] = Db::name('lt_content')
                ->where('con_mid', $menus['menu_id'])
                ->limit(10)
                ->select()
                ->toArray();
        }
        unset($menus);
        $handle = function (&$row) use ($domain) {
            $row['cover']      = $row['cover'] ?: $domain . '/static/image/logos.jpeg';
            $row['img']        = $domain . '/static/image/logos.jpeg';
            $row['views']      = mt_rand(1, 1000);
            $row['updatetime'] = date('Y-m-d', strtotime($row['updatetime']));
        };
        array_walk($content, $handle);
        array_walk($banner, $handle);
        array_walk($popolar, $handle);
        array_walk($middlecarousel, $handle);

        foreach ($bottomnav as $item) {
            array_walk($item['contents'], $handle);
        }

        $response = [
            'content' => $content,
            'banner' => $banner,
            'popolar' => $popolar,
            'menu' => $menu,
            'latest' => $latest,
            'dynamics' => $dynamics,
            'secondcontent' => $bottomnav,
            'middlecarousel' => $middlecarousel
        ];
        return view('index', $response);
    }

    public function detail()
    {
        $conid = $this->request->param('id');
        $domain = $this->request->domain();
        $defaultCover = $domain . '/static/image/logos.jpeg';
        $helper = new Helper();

        $list = $helper->getRecordByCondition('lt_content', 'con_id=' . $conid);
        $list['cover'] = empty($list['cover']) ? $defaultCover : $list['cover'];
        $list['img'] = $defaultCover;
        $list['updatetime'] = date('Y-m-d', strtotime($list['updatetime']));

        $parser = new \cebe\markdown\Markdown();
        $list['content'] = $parser->parse($list['content'] ?? '');

        $helper = new \app\common\Helper;
        $websiteId = $helper->getWebsiteId();

        $menu = $helper->menuSet($websiteId);
        $relatedInfo = $this->relatedInfo($websiteId, $this->request->domain());
        $latest = $relatedInfo['latest'];
        $dynamics = $relatedInfo['dynamics'];
        $content = $relatedInfo['content'];

        $prev = Db::name('lt_content')
            ->where('con_id', '<', $conid)
            ->order('con_id', 'desc')
            ->find();

        if (empty($prev)) {
            $prev = Db::name('lt_content')
                ->order('con_id', 'desc')
                ->find();
        }
        $prev = $prev ?: [];
        $prev['cover'] = $this->request->domain() . ($prev['cover'] ?? $defaultCover);

        $next = Db::name('lt_content')
            ->where('con_id', '>', $conid)
            ->order('con_id', 'asc')
            ->find();
        if (empty($next)) {
            $next = Db::name('lt_content')
                ->order('con_id', 'asc')
                ->find();
        }
        $next = $next ?: [];
        $next['cover'] = $this->request->domain() . ($next['cover'] ?? $defaultCover);

        $response = [
            'list' => $list,
            'menu' => $menu,
            'latest' => $latest,
            'dynamics' => $dynamics,
            'content' => $content,
            'prev' => $prev,
            'next' => $next
        ];
        return view('detail', $response);
    }


    public function cate()
    {
        $menuId   = (int)$this->request->param('id', 3);
        $page     = (int)$this->request->param('page', 1);
        $defaultCover = $this->request->domain() . '/static/image/logos.jpeg';
        $helper = new \app\common\Helper;
        $websiteId = $helper->getWebsiteId();
        if ($this->request->isAjax()) {
            $pageSize = 30;
            $query = Db::name('lt_content')
                ->where('con_mid', $menuId)
                ->order('updatetime', 'desc');

            $paginate = $query->paginate($pageSize, false, ['page' => $page]);

            $list = $paginate->items();
            foreach ($list as &$item) {
                $item['cover']      = $item['cover'] ?: $defaultCover;
                $item['img']        = $defaultCover;
                $item['updatetime'] = date('Y-m-d', strtotime($item['updatetime'])) ?? date("Y-m-d");
                $item['views'] = mt_rand(1, 1000);
            }
            unset($item);

            $response = [
                'cate_list' => $list,
                'pagination' => [
                    'current_page' => $paginate->currentPage(),
                    'last_page'    => $paginate->lastPage(),
                    'per_page'     => $paginate->listRows(),
                    'total'        => $paginate->total(),
                ],
            ];
            return json($response);
        }

        $menuTitle = Db::name('lt_menu')
            ->where('menu_id', $menuId)
            ->value('menu_title');
        $menuDescription = Db::name('lt_menu')
            ->where('menu_id', $menuId)
            ->value('menu_description');
        $menuKeywords = Db::name('lt_menu')
            ->where('menu_id', $menuId)
            ->value('menu_keywords');
        $mainCover = $list[0]['cover'] ?? $defaultCover;
        $menu = $helper->menuSet($websiteId);
        $relatedInfo = $this->relatedInfo($websiteId, $this->request->domain());
        $latest = $relatedInfo['latest'];
        $dynamics = $relatedInfo['dynamics'];
        $response = [
            'menuId' => $menuId,
            'menuTitle' => $menuTitle,
            'menuDescription' => $menuDescription,
            'menuKeywords' => $menuKeywords,
            'menu' => $menu,
            'latest' => $latest,
            'dynamics' => $dynamics,
            'menu_list'  => $menuTitle,
            'main_cover' => $mainCover,

        ];
        return view('cate', $response);
    }


    public function search()
    {
        if ($this->request->isAjax()) {
            $helper = new Helper();
            $websiteId = $helper->getWebsiteId();

            $keyword = trim($this->request->param('keyword', ''));
            if (empty($keyword)) {
                return json(['code' => 400, 'msg' => '搜索关键词不能为空', 'data' => []]);
            }

            $page = max(1, (int)$this->request->param('page', 1));
            $pageSize = min(50, (int)$this->request->param('pageSize', 30));

            $contentIds = Db::name('lt_con2website')
                ->where('cw_website_id', $websiteId)
                ->column('cw_id');

            if (empty($contentIds)) {
                return json([
                    'code' => 200,
                    'msg' => 'success',
                    'data' => [
                        'searchlist' => [],
                        'pagination' => [
                            'total' => 0,
                            'per_page' => $pageSize,
                            'current_page' => $page,
                            'last_page' => 1
                        ]
                    ]
                ]);
            }

            $where = [
                ['con_id', 'in', $contentIds],
                ['title', 'like', "%{$keyword}%"]
            ];

            $list = Db::name('lt_content')
                ->where($where)
                ->order('updatetime', 'desc')
                ->paginate([
                    'list_rows' => $pageSize,
                    'page' => $page,
                    'var_page' => 'page'
                ]);

            $domain = $this->request->domain();
            $defaultCover = $domain . '/static/image/logos.jpeg';

            $searchList = array_map(function ($item) use ($defaultCover) {
                return [
                    'con_id' => $item['con_id'],
                    'title' => $item['title'],
                    'stitle' => $item['stitle'] ?? '',
                    'editor' => $item['editor'] ?? '匿名作者',
                    'updatetime' => date('Y-m-d', strtotime($item['updatetime'])),
                    'img' => empty($item['cover']) ? $defaultCover : $item['cover'],
                    'views' => $item['views'] ?? mt_rand(1, 1000),
                    'cover' => empty($item['cover']) ? $defaultCover : $item['cover']
                ];
            }, $list->items());
            $response =  [
                'searchlist' => $searchList,
                'pagination' => [
                    'total' => $list->total(),
                    'per_page' => $pageSize,
                    'current_page' => $list->currentPage(),
                    'last_page' => $list->lastPage()
                ]
            ];
            return json($response);
        }

        $helper = new \app\common\Helper;
        $websiteId = $helper->getWebsiteId();
        $menu = $helper->menuSet($websiteId);
        return view('search', ['menu' => $menu]);
    }

    /**
     * 
     *
     * @param int    $websiteId 网站 ID
     * @param string $domain  
     * @return array 
     */
    private static function relatedInfo(int $websiteId, string $domain): array
    {
        $defaultCover = $domain . '/static/image/logos.jpeg';

        $format = function (&$row) use ($defaultCover) {
            $row['cover'] = !empty($row['cover']) ? $row['cover'] : $defaultCover;
            $row['img'] = $defaultCover;
            $row['views'] = mt_rand(1, 1000);
            $row['updatetime'] =  date('Y-m-d', strtotime($row['updatetime']));
        };

        $content = Db::name('lt_con2website')
            ->alias('c2w')
            ->join('lt_content c', 'c2w.cw_id = c.con_id')
            ->where(['c2w.cw_website_id' => $websiteId, 'c2w.is_recommend' => 1])
            ->field('c.*, c2w.views')
            ->order('c.updatetime DESC')
            ->limit(30)
            ->select()
            ->toArray();

        $latest = Db::name('lt_con2website')
            ->alias('c2w')
            ->join('lt_content c', 'c2w.cw_id = c.con_id')
            ->where(['c2w.cw_website_id' => $websiteId])
            ->whereNotNull('c.title')
            ->where('c.title', '<>', '')
            ->field('c.con_id,c.title,c.cover,c.updatetime')
            ->order('c2w.cw_updatetime DESC')
            ->limit(12)
            ->select()
            ->toArray();

        $dynamics = Db::name('lt_con2website')
            ->alias('c2w')
            ->join('lt_content c', 'c2w.cw_id = c.con_id')
            ->where(['c2w.cw_website_id' => $websiteId, 'c2w.is_news' => 1])
            ->whereNotNull('c.title')
            ->where('c.title', '<>', '')
            ->field('c.con_id,c.title,c.cover,c.updatetime')
            ->order('c2w.cw_updatetime DESC')
            ->limit(12)
            ->select()
            ->toArray();

        array_walk($latest, $format);
        array_walk($dynamics, $format);
        array_walk($content, $format);
        return [
            'latest' => $latest,
            'dynamics' => $dynamics,
            'content' => $content
        ];
    }
}
