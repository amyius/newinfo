<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use think\facade\View;

class BuildHtml extends Command
{
    protected function configure()
    {
        $this->setName('buildhtml')
            ->addArgument('id', Argument::REQUIRED, '文章ID')
            ->addOption('force', 'f', Option::VALUE_NONE, '强制覆盖')
            ->setDescription('the buildhtml command');
    }

    protected function execute(Input $input, Output $output)
    {
        $menuid = Db::name('lt_menu')->where('mid', 1)->select();
        $ltcontent = Db::name('lt_content');
        $id   = $input->getArgument('id');
        $force = $input->getOption('force');
        $path  = public_path() . 'static/article/';
        is_dir($path) || mkdir($path, 0755, true);

        $article = $ltcontent->where('con_id', $id)->find();
        if (!$article) {
            $output->error("文章 {$id} 不存在");
            return;
        }
        
        $file = $path . $id . '.html';
        if (!$force && is_file($file)) {
            $output->writeln("已存在，跳过：{$file}");
            return;
        }

        // 渲染模板
        $html = View::fetch('detail', ['article' => $article]);
        file_put_contents($file, $html);
        $output->info("已生成：{$file}");
        $output->writeln('buildhtml');
    }
}
