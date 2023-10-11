<?php
namespace Xiaosongshu\Table;
class Table
{

    /**
     * 使用表格
     * @param array $header 表格头部
     * @param array $row 表格内容
     * @return void
     * @throws \Exception
     */
    public function table(array $header,array $row){
        if (empty($header)){
            throw new \Exception("标题不能为空");
        }
        if (empty($row)){
            throw new \Exception("表格内容不能为空");
        }
        $row = array_values($row);
        $header = array_values($header);
        if (count($header)!=count($row[0])){
            throw new \Exception("标题和内容的行数不一致");
        }
        foreach ($row as $k=>$v){
            foreach ($v as $a=>$b){
                if (is_array($b)){
                    throw new \Exception("暂不支持多维数组");
                }
            }
        }
        /** 渲染表格 */
        $this->render($row,$header);
    }

    /**
     * 渲染表格
     * @param array $row
     * @param array $header
     * @return void
     */
    protected function render(array $row,array $header){
        /** 合并数据 */
        array_unshift($row,$header);
        /** 总的列数 */
        $countColumns = count($header);
        /** 每一列的宽度 */
        $allColumnsWidth = [];
        /** 复制数组 */
        $data = $row;
         /** 计算每个单元格的宽度 */
        array_walk_recursive($data,function (&$v){
            /** 因为中英文混杂，长度不一致，那么取中间值作为元素的宽度 */
            $l1= mb_strlen($v);
            $l2= strlen($v);
            $v=ceil($l1+($l2-$l1)/2);
        });
        /** 获取每一列的最大长度 */
        for ($i=0;$i<$countColumns;$i++){
            $array = array_column($data,$i);
            $allColumnsWidth[$i]=max($array);
        }

        /** 渲染表格 */
        /** 初始化表格 */
        $rows="";
        foreach ($row as $k=>$v){
            /** 换行 */
            $rows.= "\r\n";
            /** 渲染每一行的表格分割线，每一个单元格各加一个空格，防止数据紧贴分割线 */
            foreach ($allColumnsWidth as $length){
                $rows.='+-'.str_repeat('-',$length).'-';
            }
            /** 每一行的分割线封口 */
            $rows.="+\r\n";
            /** 遍历第k行数据 */
            foreach ($v as $a=>$b){
                /** 初始化单元格 默认左对齐 空格+1 */
                $rows.="| ".$b;
                /** 第a列的最大宽度 */
                $max_width=$allColumnsWidth[$a]??0;
                /** 当前数据实际宽度 */
                $width = $data[$k][$a]??0;
                /** 需要补充的空格数 */
                $nbsp = $max_width-$width;
                /** 渲染单元格空格，空格+1 对齐 */
                $rows.= str_repeat( ' ',$nbsp).' ';
            }
            /** 每一列数据封口 */
            $rows.='|';
        }
        /** 数据渲染结束换行 */
        $rows.="\r\n";
        /** 渲染每一行的表格分割线，每一个单元格各加一个空格，防止数据紧贴分割线 */
        foreach ($allColumnsWidth as $length){
            $rows.="+-".str_repeat('-',$length).'-';
        }
        /** 最后一行分割线封口 */
        $rows.="+\r\n";
        echo $rows;
    }
}