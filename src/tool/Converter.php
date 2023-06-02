<?php

namespace Meloncut\LaravelUtils\tool;

class Converter {
    /**
     * 数据列表转换成树
     *
     * @param array $dataArr     数据列表
     * @param integer $rootId    根节点ID
     * @param string $privateKey 主键
     * @param string $parentKey  父节点名称
     * @param string $childName  子节点名称
     *
     * @return array  转换后的树
     */
    public static function listToTree (array $dataArr, int $rootId = 0, $privateKey = 'id', $parentKey = 'parent_id', $childName = 'children') {
        $tree = [];
        $referList = [];

        foreach ($dataArr as $key => & $sorData) {
            $referList[$sorData[$privateKey]] =& $dataArr[$key];
        }

        foreach ($dataArr as $key => $data) {
            $pId = $data[$parentKey];
            if ($rootId == $pId) {
                $tree[] =& $dataArr[$key];
            } else {
                if (isset($referList[$pId])) {
                    $pNode =& $referList[$pId];
                    $pNode[$childName][] =& $dataArr[$key];
                }
            }
        }

        return $tree;
    }

    /**
     * 对象转数组
     * @param $array
     * @return array|mixed
     */
    public static function objectToArray ($array) {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = self::objectToArray($value);
            }
        }
        return $array;
    }
}