<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;


class newsubmitUrl extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('newsubmitUrl')
            ->setDescription('the newsubmitUrl command');
    }
    protected function execute(Input $input, Output $output)
    {
        try {
            $urlList = [];

            $cates = Db::name('lt_menu')
                ->distinct(true)
                ->where('menu_website_id', 1)
                ->order('menu_id desc')
                ->select();

            $urlList[] = 'https://www.thunderflash666.com';

            foreach ($cates as $item) {
                if (count($urlList) >= 100) {
                    break;
                }

                $urlList[] = 'https://www.thunderflash666.com/cate/' . $item['menu_id'] . '/' . $item['menu_title'] . '?page=1';

                $remainingSlots = 100 - count($urlList);
                if ($remainingSlots > 0) {
                    $shortplay = Db::name('lt_content')
                        ->where('con_mid', $item['menu_id'])
                        ->order('updatetime desc')
                        ->limit($remainingSlots)
                        ->select();

                    foreach ($shortplay as $value) {
                        $urlList[] = 'https://www.thunderflash666.com/detail/' . $value['con_id'] . '/' . $value['title'];

                        if (count($urlList) >= 100) {
                            break 2;
                        }
                    }
                }
            }

            $urlList = array_slice($urlList, 0, 100);

            $data = [
                'siteUrl' => 'https://www.thunderflash666.com',
                'urlList' => $urlList
            ];

            $output->writeln('准备提交URL数量：' . count($urlList));

            $json = json_encode($data, JSON_UNESCAPED_SLASHES);

            $apiUrl = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=f8f7030bdb284e75aa41c4100f34569c';

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \RuntimeException('CURL请求失败: ' . curl_error($ch));
            }

            $result = json_decode($response, true);

            if (isset($result['d'])) {
                $output->writeln('<info>提交成功！已提交URL数量：' . count($urlList) . '</info>');
                $output->writeln('Bing API响应：' . print_r($result['d'], true));
            } else {
                throw new \RuntimeException('提交失败，API返回异常响应：' . $response);
            }
        } catch (\Exception $e) {
            $output->writeln('<error>发生错误：' . $e->getMessage() . '</error>');
            return 1;
        } finally {
            if (isset($ch)) {
                curl_close($ch);
            }
        }

        return 0;
    }
}
