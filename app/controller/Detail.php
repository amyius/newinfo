<?php

namespace app\controller;

use app\BaseController;
use app\common\Helper;

class Detail extends BaseController
{
    //菜单分类
    public function categorize()
    {
        // 获取请求参数
        $menuId = $this->request->param('id', 3);
        $page = $this->request->param('page', 1);
        $pageSize = 30;

        $helper = new Helper();
        $domain = $this->request->domain();
        $defaultCover = $domain . '/static/img/logos.jpeg';

        $menuInfo = $helper->getRecordByCondition('lt_menu', 'menu_id=' . $menuId);

        $whereCondition = 'con_mid=' . $menuId;
        $paginateResult = $helper->paginate('lt_content', $page, $pageSize, 'desc', 'updatetime', '', $whereCondition);

        $contentList = $paginateResult['data']->toArray();
        foreach ($contentList as $key => $item) {
            if (empty($item['cover'])) {
                $contentList[$key]['cover'] = $defaultCover;
            }
            $contentList[$key]['img'] = $defaultCover;
            $contentList[$key]['updatetime'] = date('Y-m-d', strtotime($item['updatetime']));
        }

        $mainCover = empty($contentList[0]['cover']) ? $defaultCover : $contentList[0]['cover'];

        $response = [
            'menu_list' => $menuInfo['menu_title'],
            'cate_list' => $contentList,
            'main_cover' => $mainCover,
            'pagination' => [
                'current_page' => (int)$page,
                'last_page' => (int)$paginateResult['pageCount'],
                'per_page' => $pageSize,
                'total' => $paginateResult['total'],
            ],
        ];

        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $response,
        ]);
    }

    //搜索
    public function search()
    {
        $helper = new Helper();
        $websiteid = $helper->getWebsiteId();
        $condition = [
            'cw_website_id' => $websiteid
        ];

        $list = $helper->getRecordListByCondition('lt_con2website', $condition);
        $listArray = $list->toArray();

        $keyword = trim($this->request->param('keyword', ''));
        if (empty($keyword)) {
            return json([
                'code' => 400,
                'msg' => '搜索关键词不能为空',
                'data' => []
            ]);
        }

        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('pageSize', 10);

        $domain = $this->request->domain();
        $defaultCover = $domain . '/static/img/logos.jpeg';
        $searchResults = [];

        $contentIds = array_column($listArray, 'cw_id');
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

        $searchCondition = [
            ['con_id', 'in', $contentIds],
            ['title', 'like', "%{$keyword}%"]
        ];

        // 获取总数
        $total = $helper->getCount('lt_content', $searchCondition);

        // 计算分页偏移量
        $offset = ($page - 1) * $pageSize;

        $searchlist = $helper->getRecordListByCondition(
            'lt_content',
            $searchCondition,
            '',
            'updatetime desc',
            $offset . ',' . $pageSize // 添加分页限制
        );

        if ($searchlist) {
            $searchArray = $searchlist->toArray();
            foreach ($searchArray as $key => $item) {
                $searchResults[] = [
                    'con_id' => $item['con_id'],
                    'title' => $item['title'],
                    'stitle' => $item['stitle'] ?? '',
                    'author' => $item['author'] ?? 'admin',
                    'updatetime' => date('Y-m-d', $item['updatetime']) ?? '',
                    'img' => $defaultCover,
                    'views' => $item['views'] ?? 0,
                    'cover' => empty($item['cover']) ? $defaultCover : $item['cover']
                ];
            }
        }

        // 计算总页数
        $lastPage = ceil($total / $pageSize);

        $response = [
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'searchlist' => $searchResults,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $pageSize,
                    'current_page' => $page,
                    'last_page' => $lastPage
                ]
            ]
        ];
        return json($response);
    }
}
