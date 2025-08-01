<?php

namespace app\controller;

use app\BaseController;
use app\common\Helper;

class Article extends BaseController
{
    public function classification()
    {
        $helper = new Helper();
        $websiteId = $helper->getWebsiteId();
        $domain = $this->request->domain();
        $defaultCover = $domain . '/static/img/logos.jpeg';

        // 最新文章
        $newscondition = [
            'cw_website_id' => $websiteId,
        ];
        $newsfiled = 'cw_updatetime desc';
        $newsArticle = $helper->getRecordListByCondition('lt_con2website', $newscondition, '', $newsfiled, 12);
        $article = [];
        foreach ($newsArticle as $item) {
            $articlewhere = "con_id = {$item['cw_id']} "
                . "AND title IS NOT NULL "
                . "AND title <> ''";
            $articleData = $helper->getRecordListByCondition('lt_content', $articlewhere, 'con_id,title,updatetime,cover');
            foreach ($articleData as $data) {
                if ($data['cover'] == null || $data['cover'] == '') {
                    $data['cover'] = $defaultCover;
                }
                $data['img'] = $defaultCover;
                $data['updatetime'] = date('Y-m-d', strtotime($data['updatetime']));
                $article[] = $data;
            }
        }

        // 动态文章
        $dynamicscondition = [
            'cw_website_id' => $websiteId,
            'is_news' => 1
        ];
        $dynamicslist = $helper->getRecordListByCondition('lt_con2website', $dynamicscondition, '', 'cw_updatetime desc', 12);
        $dynamics = [];
        foreach ($dynamicslist as $item) {
            $dynamicwhere  = "con_id = {$item['cw_id']} "
                . "AND title IS NOT NULL "
                . "AND title <> ''";
            $dynamicData = $helper->getRecordListByCondition('lt_content', $dynamicwhere, 'con_id,title,updatetime,cover');
            foreach ($dynamicData as $data) {
                if ($data['cover'] == null || $data['cover'] == '') {
                    $data['cover'] = $defaultCover;
                }
                $data['img'] = $defaultCover;
                $data['updatetime'] = date('Y-m-d', strtotime($data['updatetime']));
                $dynamics[] = $data;
            }
        }

        // 导航相关文章
        $navcondition = [
            'menu_website_id' => $websiteId,
            'menu_istop' => 2,
            'menu_pid' => 0
        ];
        $navlist = $helper->getRecordListByCondition('lt_menu', $navcondition, '', '', 5);
        $nav = [];
        foreach ($navlist as $item) {
            $navData = $helper->getRecordListByCondition('lt_content', 'con_mid=' . $item['menu_id']);
            foreach ($navData as $key => $data) {
                if ($data['cover'] == null || $data['cover'] == '') {
                    $data['cover'] = $defaultCover;
                }
                $data['img'] = $defaultCover;
                $data['cover'] = empty($data['cover']) ? $defaultCover : $data['cover'];
                $data['updatetime'] = date('Y-m-d', strtotime($data['updatetime']));
                $nav[] = $data;
                $nav[$key]['menu_title'] = $item['menu_title'];
            }
        }

        $data = [
            'newsArticle' => $article,
            'dynamics' => $dynamics,
            'nav' => $nav
        ];

        $response = [
            'code' => 200,
            'msg' => 'success',
            'data' => $data
        ];
        return json($response);
    }
}
