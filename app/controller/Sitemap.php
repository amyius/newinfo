<?php

namespace app\controller;

use app\BaseController;
use think\facade\Log;
use think\facade\Db;
use think\facade\Request;
use think\Response;

class Sitemap extends BaseController
{
    public function index()
    {
        $baseUrl = Request::domain();

        try {
            $cates = Db::name('lt_menu')
                ->distinct(true)
                ->where('menu_website_id', 1)
                ->order('menu_id desc')
                ->select();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            $xml .= '
                <url>
                    <loc>' . htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') . '</loc>
                    <lastmod>' . date('Y-m-d') . '</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>1.0</priority>
                </url>';

            foreach ($cates as $cate) {
                $safeTitle = htmlspecialchars($cate['menu_title'], ENT_QUOTES, 'UTF-8');
                $safeBaseUrl = htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8');
                $safeUrl = $safeBaseUrl . '/cate/' . $cate['menu_id'] . '/' . $safeTitle . '?page=1';

                $xml .= '
                    <url>
                        <loc>' . $safeUrl . '</loc>
                        <lastmod>' . date('Y-m-d') . '</lastmod>
                        <changefreq>daily</changefreq>
                        <priority>0.8</priority>
                    </url>';

                $articles = Db::name('lt_content')
                    ->where('con_mid', $cate['menu_id'])
                    ->order('con_id desc')
                    ->select();

                foreach ($articles as $article) {
                    $safeArticleTitle = htmlspecialchars($article['title'] ?? '', ENT_QUOTES, 'UTF-8');
                    $safeArticleUrl = $safeBaseUrl . '/detail/' . $article['con_id'] . '/' . $safeArticleTitle;

                    $xml .= '
                        <url>
                            <loc>' . $safeArticleUrl . '</loc>
                            <lastmod>' . date('Y-m-d') . '</lastmod>
                            <changefreq>weekly</changefreq>
                            <priority>0.6</priority>
                        </url>';
                }
            }

            $xml .= '
            </urlset>';

            return Response::create($xml, 'xml')->contentType('text/xml; charset=utf-8');
        } catch (\Exception $e) {
            Log::error('Sitemap generation error: ' . $e->getMessage());
            return Response::create('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>', 'xml');
        }
    }
}
