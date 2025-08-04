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
        $cacheFile = runtime_path() . 'sitemap.xml';
        $cacheTime = 86400; 

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            return Response::create(file_get_contents($cacheFile), 'xml')
                ->contentType('text/xml; charset=utf-8');
        }

        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'sitemap');
            $handle = fopen($tempFile, 'w');

            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>');
            fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

            $baseUrl = Request::domain();
            fwrite($handle, '
            <url>
                <loc>' . htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') . '</loc>
                <lastmod>' . date('Y-m-d') . '</lastmod>
                <changefreq>daily</changefreq>
                <priority>1.0</priority>
            </url>');

            $validCategories = Db::name('lt_menu')
                ->field('menu_id,menu_title')
                ->where('menu_website_id', 1)
                ->order('menu_id desc')
                ->select();

            foreach ($validCategories as $category) {
                $safeTitle = htmlspecialchars($category['menu_title'], ENT_QUOTES, 'UTF-8');
                $categoryUrl = htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') .
                    '/cate/' . $category['menu_id'] . '/' . $safeTitle . '?page=1';

                fwrite($handle, '
                <url>
                    <loc>' . $categoryUrl . '</loc>
                    <lastmod>' . date('Y-m-d') . '</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>0.8</priority>
                </url>');

                Db::name('lt_content')
                    ->field('con_id,title')
                    ->where('con_mid', $category['menu_id'])
                    ->order('con_id desc')
                    ->chunk(500, function ($articles) use ($handle, $baseUrl) {
                        foreach ($articles as $article) {
                            $safeTitle = htmlspecialchars($article['title'] ?? '', ENT_QUOTES, 'UTF-8');
                            $articleUrl = htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') .
                                '/detail/' . $article['con_id'] . '/' . $safeTitle;

                            fwrite($handle, '
                            <url>
                                <loc>' . $articleUrl . '</loc>
                                <lastmod>' . date('Y-m-d') . '</lastmod>
                                <changefreq>weekly</changefreq>
                                <priority>0.6</priority>
                            </url>');
                        }
                    });
            }

            fwrite($handle, '</urlset>');
            fclose($handle);

            rename($tempFile, $cacheFile);

            return Response::create(file_get_contents($cacheFile), 'xml')
                ->contentType('text/xml; charset=utf-8');
        } catch (\Exception $e) {
            Log::error('Sitemap生成错误: ' . $e->getMessage());

            return Response::create(
                '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>',
                'xml',
                200,
                ['Content-Type' => 'text/xml; charset=utf-8']
            );
        }
    }
}
