<?php

/**
 * @purpose 深度优先算法
 * 给你一个 m x n 的矩阵 board ，由若干字符 'X' 和 'O' 组成，捕获 所有 被围绕的区域：
 *
 * 连接：一个单元格与水平或垂直方向上相邻的单元格连接。
 * 区域：连接所有 'O' 的单元格来形成一个区域。
 * 围绕：如果您可以用 'X' 单元格 连接这个区域，并且区域中没有任何单元格位于 board 边缘，则该区域被 'X' 单元格围绕。
 * 通过将输入矩阵 board 中的所有 'O' 替换为 'X' 来 捕获被围绕的区域。
 *
 * 首先对边界上每一个'O'做深度优先搜索，将与其相连的所有'O'改为'-'。然后遍历矩阵，将矩阵中所有'O'改为'X',将矩阵中所有'-'变为'O'
 * @link https://leetcode.cn/problems/surrounded-regions/description/?envType=study-plan-v2&envId=top-interview-150
 */
class BoardSolver {
    private $row;  // 定义私有属性存储行数
    private $col;  // 定义私有属性存储列数

    // 解决问题的方法
    public function solve(&$board) {
        // 如果输入的二维数组为空或者长度为 0，直接返回
        if ($board === null || count($board) === 0) {
            return;
        }
        // 获取二维数组的行数和列数
        $this->row = count($board);
        $this->col = count($board[0]);

        // 对第一列和最后一列的每个元素进行深度优先搜索
        for ($i = 0; $i < $this->row; $i++) {
            $this->dfs($board, $i, 0);
            $this->dfs($board, $i, $this->col - 1);
        }
        // 对第一行和最后一行的每个元素进行深度优先搜索
        for ($j = 0; $j < $this->col; $j++) {
            $this->dfs($board, 0, $j);
            $this->dfs($board, $this->row - 1, $j);
        }

        // 遍历整个二维数组进行标记的转换
        for ($i = 0; $i < $this->row; $i++) {
            for ($j = 0; $j < $this->col; $j++) {
                // 如果元素是 'O'，将其标记为 'X'
                if ($board[$i][$j] == 'O') {
                    $board[$i][$j] = 'X';
                }
                // 如果元素是 '-'，将其标记为 'O'
                if ($board[$i][$j] == '-') {
                    $board[$i][$j] = 'O';
                }
            }
        }
        return;
    }

    // 深度优先搜索的方法

    /**
     * @param $board
     * @param $i
     * @param $j
     * @return void
     * @note 首先对边界上每一个'O'做深度优先搜索，将与其相连的所有'O'改为'-'。然后遍历矩阵，将矩阵中所有'O'改为'X',将矩阵中所有'-'变为'O'
     * @note 这个算法的重点是只对边界上的O做深度优先搜索，
     * @note 这一道题的意思是：只要O没有和边界接壤，都变成X。他么的题都描述不清楚，太坑了吧
     */
    public function dfs(&$board, $i, $j) {
        // 如果当前位置超出边界或者元素不是 'O'，直接返回
        if ($i < 0 || $j < 0 || $i >= $this->row || $j >= $this->col || $board[$i][$j]!= 'O') {
            return;
        }
        // 将当前位置的 'O' 标记为 '-'
        $board[$i][$j] = '-';
        // 对当前位置的上下左右位置进行递归的深度优先搜索
        $this->dfs($board, $i - 1, $j);
        $this->dfs($board, $i + 1, $j);
        $this->dfs($board, $i, $j - 1);
        $this->dfs($board, $i, $j + 1);
        return;
    }
}

// 示例用法
$board = [
    ['X', 'X', 'X', 'X'],
    ['X', 'O', 'O', 'X'],
    ['X', 'X', 'O', 'X'],
    ['X', 'O', 'X', 'X']
];
$board =[["X","X","X","X"],["X","O","O","X"],["X","X","O","X"],["X","O","X","X"]];
$boardSolver = new BoardSolver();
$boardSolver->solve($board);
print_r($board);
?>