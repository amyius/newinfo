<?php

namespace app\common;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use app\BaseController;

class Helper
{
    protected $table;
    protected $model;

    public static function getRecordByCondition($table, $condition, $field = '*', $order = [])
    {
        $query = Db::table($table)->field($field)->where($condition);
        if ($order) {
            $query->order($order);
        }
        $result = $query->find();
        return $result ?: [];
    }

    public static function getRecordListByCondition($table, $condition = [], $field = '*', $order = [], $limit = 0)
    {
        $query = Db::table($table)->field($field);
        if ($condition) {
            $query->where($condition);
        }
        if ($order) {
            $query->order($order);
        }
        if ($limit !== 0) {
            $query->limit((int)$limit);
        }

        $result = $query->select();
        return $result ?: [];
    }

    public static  function getValue($table, $id, $columnName)
    {
        return Db::table($table)->where('id', $id)->value($columnName);
    }

    public static function getCount($table, $condition)
    {
        return Db::table($table)->where($condition)->count();
    }

    public static function update($table, $data, $where)
    {
        return Db::table($table)->where($where)->update($data);
    }

    public static function getById($table, $id)
    {
        return Db::table($table)->find($id);
    }

    public static function getHeaderUrl()
    {
        $origin = Request::header('origin');

        $origin = str_replace('http://', '', $origin);

        $origin = str_replace('https://', '', $origin);
        return $origin;
    }

    public function getWebsiteId()
    {
        // $url = $this->getHeaderUrl();
        // $websiteid = $this->getRecordByCondition('lt_website', 'website_domain=' . "'$url'");
        // return $websiteid['website_id'];
        return 1;
    }

    public static function paginate($table, $page = 1, $pageSize = 10, $order = 'desc', $sort = '', $defaultSort = '', $condition = [])
    {
        if ($defaultSort != "") {
            $defaultSort = ', ' . $defaultSort;
        }
        $sort = ($sort != '' ? $sort . ' ' . $order . $defaultSort : 'updatetime desc');

        $query = Db::table($table)->where($condition);

        $total = $query->count();
        $pageCount = ceil($total / $pageSize);

        if ($total == 0) {
            $data = [];
        } else {
            $list = $query->order($sort)
                ->page($page, $pageSize)
                ->select();

            $data = $list ?: [];
        }

        return [
            'data' => $data,
            'total' => $total,
            'pageCount' => $pageCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }

    //获取菜单
    public static function menuSet(int $websiteId): array
    {
        $rows = Db::name('lt_menu')
            ->where('menu_pid', 0)
            ->where('menu_website_id', $websiteId)
            ->where(function ($q) {
                $q->where('menu_navtop', 1)
                    ->whereOr('menu_istop', 1)
                    ->whereOr('menu_navbottom', 1);
            })
            ->select()
            ->toArray();

        $menu = [
            'menu_top'     => [],
            'menu_middle1' => [],
            'menu_bottom'  => []
        ];
        foreach ($rows as $r) {
            if ($r['menu_navtop'])    $menu['menu_top'][]    = $r;
            if ($r['menu_istop'])     $menu['menu_middle1'][] = $r;
            if ($r['menu_navbottom']) $menu['menu_bottom'][] = $r;
        }
        return $menu;
    }
}
