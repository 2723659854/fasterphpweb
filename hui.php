<?php

/**
 * @purpose 回朔算法
 * @note 八皇后问题：要求在一个 8x8 的棋盘上放置 8 个皇后，使得彼此之间不会互相攻击（即任意两个皇后不能处于同一行、同一列或同一斜线上）
 * @comment 类似于穷举法，列举出所有可能的方案
 */
class EightQueens {
    private $result = []; // 存储最终的解决方案
    private $cols = [];   // 标记列是否有皇后
    private $main_diag = []; // 标记主对角线是否有皇后
    private $anti_diag = []; // 标记副对角线是否有皇后

    function solveNQueens($n) {
        $this->dfs($n, 0, []); // 调用深度优先搜索函数开始解决问题
        return $this->result; // 返回所有解决方案
    }

    function dfs($n, $row, $current) {
        if ($row == $n) { // 如果已经放置了 n 个皇后，即找到一个解决方案
            $this->result[] = $current; // 将当前解决方案添加到结果中
            return;
        }

        // 尝试在当前行的每一列放置皇后
        for ($col = 0; $col < $n; $col++) {
            /** 如果满足条件，就执行，否则换另外一种方法 */
            // 检查当前列、主对角线、副对角线是否已经有皇后
            if (!$this->cols[$col] && !$this->main_diag[$row - $col + $n - 1] && !$this->anti_diag[$row + $col]) {
                // 标记当前列、主对角线、副对角线已放置皇后
                $this->cols[$col] = true;
                /** $row - $col 计算出当前方格在主对角线上的位置关系  $n - 1 是为了确保结果非负，因为索引从 0 开始 */
                $this->main_diag[$row - $col + $n - 1] = true;
                /** 对于任意位置 (row, col)，它所在的副对角线上的所有位置满足 row + col 的值相等 */
                $this->anti_diag[$row + $col] = true;

                // 构建当前行的棋盘状态，并递归到下一行继续放置皇后
                $board = array_fill(0, $n, '.'); // 创建一个空棋盘
                $board[$col] = 'Q'; // 在当前列放置皇后
                /** 在当前行，当前列防止皇后之后，在当前状态下，处理下一行 */
                $this->dfs($n, $row + 1, array_merge($current, [implode('', $board)])); // 递归调用下一行
                /** 当前行，当前列放置皇后的方案处理完成之后，恢复当前列的状态，处理下一列 */
                // 恢复当前列、主对角线、副对角线的状态，进行回溯
                $this->cols[$col] = false;
                $this->main_diag[$row - $col + $n - 1] = false;
                $this->anti_diag[$row + $col] = false;
            }
        }
    }
}

// 测试
$eightQueens = new EightQueens();
$solutions = $eightQueens->solveNQueens(8); // 求解八皇后问题

// 输出所有解决方案
foreach ($solutions as $solution) {
    foreach ($solution as $row) {
        echo "$row\n"; // 打印每一行的棋盘状态
    }
    echo "----------\n"; // 分隔不同的解决方案
}


