<?php

/**
 * 力扣算法题解法
 * @link https://leetcode.cn/studyplan/top-interview-150/
 * https://www.zhihu.com/question/26530631
 * https://www.zhihu.com/question/578847091
 * @note 算法的解题方法：将数据模型转换为几何图形，将几何图形的变化过程用代码表示出来
 */
class Solution
{

    /**
     * @param Integer[] $nums1
     * @param Integer $m
     * @param Integer[] $nums2
     * @param Integer $n
     * @return NULL
     * @link https://leetcode.cn/problems/merge-sorted-array/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function merge(array $nums1, int $m, array $nums2, int $n)
    {

        if ($n == 0) {
            return $nums1;
        }
        if ($n > 0) {
            for ($i = 0; $i < $n; $i++) {

                $nums1[$m + $i] = $nums2[$i];
            }
        }
        sort($nums1);
        return $nums1;
    }

    /**
     * @param Integer[] $nums
     * @param Integer $val
     * @return array
     * @link https://leetcode.cn/problems/remove-element/?envType=study-plan-v2&envId=top-interview-150
     */
    function removeElement(array $nums, int $val)
    {

        $total = count($nums);
        foreach ($nums as $k => $v) {
            if ($v == $val) {
                $nums[$k] = null;
                $total--;
            }
        }
        return ['total' => $total, 'array' => $nums];
    }

    /**
     * @param array $nums
     * @return array
     * @link https://leetcode.cn/problems/remove-duplicates-from-sorted-array/?envType=study-plan-v2&envId=top-interview-150
     */
    function removeDuplicates(array $nums): array
    {
        $new = [];
        foreach ($nums as $value) {
            if (!isset($new[$value])) {
                $new[$value] = $value;
            }
        }
        return ['total' => count($new), 'array' => $new];
    }

    /**
     * @param array $nums
     * @return array
     * @link https://leetcode.cn/problems/remove-duplicates-from-sorted-array-ii/?envType=study-plan-v2&envId=top-interview-150
     */
    function removeDuplicates2(array $nums)
    {
        $new = [];
        foreach ($nums as $key => $value) {
            if (!isset($new[$value])) {
                $new[$value] = 1;
            } else {
                $new[$value]++;
                if ($new[$value] > 2) {
                    $nums[$key] = null;
                }
            }
        }
        foreach ($nums as $key => $value) {
            if ($value == null) {
                unset($nums[$key]);
            }
        }
        return ['total' => count($nums), 'array' => $nums];
    }


    /**
     * @param array $nums
     * @return int
     * @link https://leetcode.cn/problems/majority-element/?envType=study-plan-v2&envId=top-interview-150
     */
    function majorityElement(array $nums): int
    {
        $new = [];
        foreach ($nums as $value) {
            if (!isset($new[$value])) {
                $new[$value] = 1;
            } else {
                $new[$value]++;
            }
        }
        $max = max($new);
        return array_search($max, $new);
    }

    /**
     * @param array $nums
     * @param int $k
     * @return array
     * @link https://leetcode.cn/problems/rotate-array/?envType=study-plan-v2&envId=top-interview-150
     */
    function rotate(array $nums, int $k): array
    {

        $length = count($nums);
        $partArray = array_slice($nums, $length - $k);
        $newArray = array_slice($nums, 0, $length - $k);
        return array_merge($partArray, $newArray);

    }

    /**
     * @param Integer[] $prices
     * @return Integer
     * @note 这里用的动态规划，也可以硬算，但是太浪费时间了
     * @link https://leetcode.cn/problems/best-time-to-buy-and-sell-stock/?envType=study-plan-v2&envId=top-interview-150
     */
    function maxProfit(array $prices)
    {
        /** 假设第一天是在最低买入 */
        $min = $prices[0];
        /** 利润 */
        $profit = 0;
        /** 卖出 */
        for ($i = 1; $i < count($prices); $i++) {
            /** 卖出 */
            $temp = $prices[$i] - $min;
            /** 有利润 */
            if ($temp > 0) {
                $profit = max($profit, $temp);
            } else {
                /** 今天价格比买入低，会亏本 ，那么选择这一天买入 */
                $min = $prices[$i];
            }
        }
        return $profit;
    }

    /**
     * 上升趋势算法
     * @param array $prices
     * @return int|mixed
     * @note 只要有利润马上就卖，然后买入
     * @link https://leetcode.cn/problems/best-time-to-buy-and-sell-stock-ii/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function maxProfit2(array $prices)
    {

        if (count($prices) == 0) {
            return 0;
        }
        $profit = 0;
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i] > $prices[$i - 1]) {
                $profit += $prices[$i] - $prices[$i - 1];
            }
        }
        return $profit;
    }

    /**
     * @param Integer[] $nums
     * @return Boolean
     * @link https://leetcode.cn/problems/jump-game/?envType=study-plan-v2&envId=top-interview-150
     */
    function canJump(array $nums)
    {
        $index = 0;
        $end = count($nums) - 1;
        while ($index < $end) {
            $step = $nums[$index];
            $index = $index + $step;
            if ($index == $end) {
                return true;
            }
            if ($step == 0) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param Integer[] $nums
     * @return Integer
     * @note 感觉脑子不够用，看不懂
     * @link https://leetcode.cn/problems/jump-game-ii/?envType=study-plan-v2&envId=top-interview-150
     */
    function jump(array $nums)
    {

        $n = count($nums); // 获取数组的长度
        if ($n == 1) {
            return 0; // 如果数组长度为1，不需要跳跃
        }

        $dp = array_fill(0, $n, PHP_INT_MAX); // 初始化 dp 数组，使用最大整数值作为初始值
        $dp[0] = 0; // 起始位置不需要跳跃
        /** 从第一个数开始跳跃 */
        for ($i = 0; $i < $n; $i++) {
            /** 可以选择跳跃的长度是 [0 , $nums[$i] ] ,在跳跃的过程中寻找最大的步数 */
            for ($j = 0; $j <= $nums[$i]; $j++) { // 遍历从当前位置的跳跃范围
                if ($i + $j < $n) { // 确保跳跃后的索引在数组范围内
                    /** 一次跳跃一步 $dp[$i] + 1 表示从 i 跳跃到 i+j 只需要一步，因为 $j <= $nums[$i],那么跳跃长度在$nums[$i]范围内，都是1步 */
                    $dp[$i + $j] = min($dp[$i] + 1, $dp[$i + $j]); // 更新最小跳跃次数
                }
            }
        }

        return $dp[$n - 1]; // 返回到达数组末尾所需的最小跳跃次数
    }

    /**
     * @param Integer[] $nums
     * @return Integer[]
     * @link https://leetcode.cn/problems/product-of-array-except-self/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function productExceptSelf($nums)
    {

        /** 复杂度是O(n) */
        $n = count($nums);
        $array = array_fill(0, $n, 1);

        $pre = 1;
        $suf = 1;

        /** 先直接取前乘积 然后累乘 每一个元素避开自己 */
        for ($i = 0; $i < $n; $i++) {
            $array[$i] = $pre;
            $pre = $pre * $nums[$i];
        }
        /** 前积乘后积，先存后乘避开自己 */
        for ($i = $n - 1; $i >= 0; $i--) {
            /** 乘后集，先避开自身 */
            $array[$i] *= $suf;
            /** 累乘后集 */
            $suf *= $nums[$i];
        }


        return $array;
    }

    /**
     * @param Integer[] $gas
     * @param Integer[] $cost
     * @return Integer
     * @link https://leetcode.cn/problems/gas-station/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function canCompleteCircuit($gas, $cost)
    {

        $array = [];
        $index = -1;
        for ($i = 0; $i < count($gas); $i++) {
            $array[$i] = $gas[$i] - $cost[$i];
            if ($array[$i] > 0 && $index == -1) {
                $index = $i;
            }
        }
        $sum = array_sum($array);
        if ($sum < 0) {
            return -1;
        }
        return $index;

    }

    /**
     * 木桶盛水
     * @param Integer[] $height
     * @return Integer
     * https://leetcode.cn/problems/trapping-rain-water/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function trap($height)
    {
        $start = 0;
        $end = 0;
        /** 形成的桶 */
        $water = [];
        /** 遍历所有的板子 */
        for ($i = 0; $i < count($height); $i++) {
            /** 遇到板子高度大于0，则认为可以形成木桶，标记为起点 */
            if ($height[$i] > 0) {
                $start = $i;
            }
            /** 找到另外一块高度大于等于起点的板子作为终点，形成木桶 */
            for ($j = $i + 1; $j < count($height); $j++) {
                if ($height[$j] >= $height[$i]) {
                    $end = $j;
                    /** 木桶的容积由最低的一块板子决定，找到了第一块高于起点的板子，形成了木桶 */
                    break;
                }
            }

            /** 两块板子之间必须有空隙才能盛水 */
            if (($end - $start) >= 2) {
                /** 用木桶盛水原理，最低的一块板子决定盛水 */
                $banzi = min($height[$start], $height[$end]);
                /** 木桶里面有砖头 ，找到木桶里面的砖头 */
                $zhuantou = [];
                for ($k = $start + 1; $k < $end; $k++) {
                    $zhuantou[] = $height[$k];
                }

                /** 水的体积 = 整个木桶的体积 - 砖头的体积 - 桶自身的体积 */
                $water[] = $banzi * ($end - $start + 1) - array_sum($zhuantou) - 2 * $banzi;
                /** 以当前的板子作为下一个桶的起点 */
                $start = $end;
                /** 从当前桶的终点开始搜索下一个桶 因为i已经+1，所以这里要移动指针-1归位 */
                $i = $end - 1;
            }
        }
        return array_sum($water);
    }

    /**
     * 罗马数字转阿拉伯数
     * @param String $s
     * @return Integer
     * @link https://leetcode.cn/problems/roman-to-integer/?envType=study-plan-v2&envId=top-interview-150
     */
    function romanToInt($s)
    {
        $sum = $this->getNumber($s[strlen($s) - 1]);
        for ($i = 0; $i < strlen($s) - 1; $i++) {
            if ($this->getNumber($s[$i]) >= $this->getNumber($s[$i + 1])) {
                $sum += $this->getNumber($s[$i]);
            } else {
                $sum -= $this->getNumber($s[$i]);
            }
        }
        return $sum;
    }

    public function getNumber($ch)
    {
        switch ($ch) {
            case 'I' :
                return 1;
            case 'V' :
                return 5;
            case 'X' :
                return 10;
            case 'L' :
                return 50;
            case 'C' :
                return 100;
            case 'D' :
                return 500;
            case 'M' :
                return 1000;
            default :
                return 0;
        }
    }


    /**
     * @param String $s
     * @param Integer $numRows
     * @return String
     * @link https://leetcode.cn/problems/zigzag-conversion/description/?envType=study-plan-v2&envId=top-interview-150
     * @note 复杂度为O(n) 关键点是正确计算每一个字符的坐标
     */
    function convert($s, $numRows)
    {
        $length = strlen($s);
        if ($length == 1) {
            return $s;
        }
        /** 一个单位需要的字符长度 */
        $number = 2 * ($numRows - 1);
        /** 字符串指针 */
        $i = 0;
        /** 初始化每一行都为空 */
        $other = array_fill(0, $numRows, "");
        /** 纵向排列字符，逐个移动字符指针 */
        while ($i < $length) {
            /** 字符在一个单位中的行坐标 */
            $index = $i % $number;
            /** 如果坐标大于等于行数 ，倒序排列 */
            $other[($index < $numRows) ? $index : ($number - $index)] .= $s[$i++] ?? "";
        }

        return implode("", $other);
    }

    /**
     * @param String[] $words
     * @param Integer $maxWidth
     * @return String[]
     * @link https://leetcode.cn/problems/text-justification/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function fullJustify($words, $maxWidth)
    {
        $information = [];
        /** 先计算每一个单词的长度 */
        foreach ($words as $key => $word) {

            $information[] = [
                'length' => strlen($word),
                'value' => $word
            ];
        }

        $rows = [];
        $index = 0;
        /** 逐行填充单词 ，若长度大于宽度，则将单词填充到下一行，本行使用空格，并且空格平均分配，以单词结尾 */
        $input = [];
        $length = 0;
        /** 逐个处理字符 */
        while ($index < count($information)) {
            /** 当前的长度 = 空格 + 单词的长度 两个单词之间最少一个空格 */
            $length = count($input) + $information[$index]['length'] + $length;

            /** 如果添加这个单词长度会超过，则新起一行 */
            if ($length >= $maxWidth) {
                $rows[] = $input;
                /** 清空数据，并初始化长度 */
                $input = [];
                $length = 0;
                /** 更新长度 */
                $length += $information[$index]['length'];
            }
            /** 更新内容 */
            $input[] = $information[$index];
            /** 更新指针 */
            $index++;
            /** 已读取所有数据，需要把剩下的数据取出 */
            if ($index == count($information)) {
                $rows[] = $input;
            }
        }

        /** 最终排版 */
        $array = [];
        /** 排版 计算每一行需要的空格数 */

        foreach ($rows as $row) {
            $count = (count($row) - 1);
            $count = $count > 0 ? $count : 1;
            $factLength = array_sum(array_column($row, 'length'));
            $space = " ";
            $emptyCount = $maxWidth - $factLength;
            /** 单词之间的空格数 */
            $perSpace = floor($emptyCount / $count);
            /** 剩余的空格数 需要填充到最后一个单词之前 */
            $overSpace = $emptyCount - $perSpace * $count;

            $string = "";
            foreach ($row as $i => $v) {
                if ($i == ($count - 1)) {
                    $perSpace = $overSpace + $perSpace;
                }
                $string .= $perSpace > 0 ? str_repeat($space, $perSpace) . $v['value'] : $v['value'];
            }
            /** 去除多余空格 */
            $string = trim($string);
            $str_length = strlen($string);
            $need = ($maxWidth - $str_length);
            /** 保证文字左对齐 */
            $array[] = $need > 0 ? $string . str_repeat($space, $need) : $string;
        }
        return $array;
    }

    /**
     * 是否回文
     * @param String $s
     * @return Boolean
     * @link https://leetcode.cn/problems/valid-palindrome/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function isPalindrome($s)
    {
        $s = strtolower(trim($s));
        preg_match_all('/[a-zA-Z]/', $s, $matches);
        $s = implode('', $matches[0]);
        $length = strlen($s);
        if ($length <= 1) {
            return true;
        }
        $mid = floor($length / 2);
        for ($i = 0; $i < $mid; $i++) {
            if ($s[$i] != $s[$length - $i - 1]) {
                return false;
            }
        }
        return true;
    }

    /**
     * 是否子序列
     * @param String $s
     * @param String $t
     * @return Boolean
     * @link https://leetcode.cn/problems/is-subsequence/?envType=study-plan-v2&envId=top-interview-150
     */
    function isSubsequence($s, $t)
    {
        $length = strlen($s);
        $fatherLength = strlen($t);
        $step = 0;
        /** 坐标 */
        $array = [];
        for ($i = 0; $i < $length; $i++) {
            /** 更新起点*/
            for ($j = $step; $j < $fatherLength; $j++) {
                /** 在集合中找到了这个字符 */
                if ($s[$i] == $t[$j]) {
                    $array[] = $j;
                    /** 保证字符串的顺序 */
                    $step = $j;
                    break;
                }
            }
        }
        if (count($array) == $length) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Integer[] $numbers
     * @param Integer $target
     * @return Integer[]
     * @link https://leetcode.cn/problems/two-sum-ii-input-array-is-sorted/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function twoSum($numbers, $target)
    {
        foreach ($numbers as $key => $value) {
            $cha = $target - $value;
            $other = array_slice($numbers, $key + 1);
            if (in_array($cha, $other)) {
                $index = array_search($cha, $other) + $key + 2;
                return [$key + 1, $index];
            }
        }
        return [0, 0];
    }

    /**
     * @param Integer[] $height
     * @return Integer
     * @link https://leetcode.cn/problems/container-with-most-water/?envType=study-plan-v2&envId=top-interview-150
     */
    function maxArea($height)
    {
        $count = count($height);
        $maxArea = 0;
        /** 就是求最大的面积 */
        for ($j = 0; $j < $count; $j++) {
            for ($i = $j + 1; $i < $count; $i++) {
                $width = $i - $j;
                $heights = min($height[$i], $height[$j]);
                $area = $width * $heights;
                $maxArea = max($maxArea, $area);
            }
        }
        return $maxArea;
    }

    /**
     * @param Integer[] $nums
     * @return Integer[][]
     * @link https://leetcode.cn/problems/3sum/?envType=study-plan-v2&envId=top-interview-150
     */
    function threeSum($nums)
    {

        $array = [];
        /** 三个坐标不同的数的和 = 0 */
        $length = count($nums);
        for ($i = 0; $i < $length; $i++) {
            for ($j = $i + 1; $j < $length; $j++) {
                $sum = $nums[$i] + $nums[$j];
                $target = -$sum;
                if (in_array($target, $nums)) {
                    $index = array_search($target, $nums);
                    if (!in_array($index, [$i, $j])) {
                        /** 去重 */
                        $small = [$nums[$i], $nums[$j], $nums[$index]];
                        sort($small);
                        if (!in_array($small, $array)) {
                            $array[] = $small;
                        }
                    }
                }
            }
        }
        return $array;
    }

    /**
     * 本题对数据
     * @param Integer $target
     * @param Integer[] $nums
     * @return Integer
     * @link https://leetcode.cn/problems/minimum-size-subarray-sum/?envType=study-plan-v2&envId=top-interview-150
     * @note 滑动窗口算法：获取到第一个满足条件长度的滑块，然后往右滑动一格，然后左侧减去一格，判断是否满足条件，循环执行。
     */
    function minSubArrayLen($target, $nums)
    {

        /** 双指针 */
        $left = 0;
        $sum = 0;
        /** 初始化最小长度 */
        $minLength = PHP_INT_MAX;
        /** 滑块向右滑动 */
        for ($right = 0; $right < count($nums); $right++) {
            /** 往右滑动滑块 */
            $sum += $nums[$right];
            /** 如果满足条件 */
            while ($sum >= $target) {
                /** 更新最小长度 */
                $minLength = min($minLength, $right - $left + 1);
                /** 左侧缩短一格 */
                $sum -= $nums[$left];
                /** 更新滑块左侧指针 */
                $left++;
            }
        }

        return ($minLength == PHP_INT_MAX) ? 0 : $minLength;
    }

    /**
     * @param int $target
     * @param array $nums
     * @return int|mixed
     * @link https://leetcode.cn/problems/minimum-size-subarray-sum/description/?envType=study-plan-v2&envId=top-interview-150
     */
    public function minSubArrayLen2(int $target, array $nums)
    {
        $i = 0;
        $j = 0;
        $minLen = PHP_INT_MAX;
        $tempSum = 0;
        while ($i <= $j && $j < count($nums)) {
            if ($tempSum < $target) {
                $tempSum += $nums[$j];
            } else {
                $tempSum -= $nums[$i++];
            }
            if ($tempSum >= $target) {
                $minLen = min($minLen, $j - $i + 1);
            } else {
                $j++;
            }
        }
        return $minLen == PHP_INT_MAX ? 0 : $minLen;
    }

    /**
     * @param String $s
     * @return Integer
     * @link https://leetcode.cn/problems/longest-substring-without-repeating-characters/?envType=study-plan-v2&envId=top-interview-150
     */
    function lengthOfLongestSubstring($s)
    {
        $m = 0;  // 初始化变量 $m 为 0，用于存储最长无重复子串的长度
        $n = strlen($s);  // 获取输入字符串 $s 的长度，并将其存储在 $n 中
        $p = 0;  // 初始化变量 $p 为 0，用于标记子串的起始位置

        for ($i = 0; $i < $n; $i++) {  // 外层循环，从字符串的开头逐个遍历到结尾
            for ($j = $p; $j < $i; $j++) {  // 内层循环，从子串的起始位置到当前外层循环的位置之前
                if ($s[$j] == $s[$i]) {  // 如果发现当前位置 $i 的字符与之前位置 $j 的字符重复
                    $p = $j + 1;  // 更新子串的起始位置为重复字符的下一个位置
                    break;  // 跳出内层循环
                }
            }
            $m = max($m, $i - $p + 1);  // 计算当前无重复子串的长度，并与之前记录的最大长度进行比较和更新
        }
        return $m;  // 返回最长无重复子串的长度
    }

    /**
     * @param String $s
     * @param String $t
     * @return String
     * @link https://leetcode.cn/problems/minimum-window-substring/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function minWindow($s, $t)
    {
        if ($s == $t) {
            return $s;
        }
        /** 初始化结果集 */
        $back = [];
        /** 将字符串切割为数组 */
        $s_array = [];
        for ($i = 0; $i < strlen($s); $i++) {
            $s_array[$i] = $s[$i];
        }
        /** 字符串切割为数组 */
        $t_array = [];
        for ($i = 0; $i < strlen($t); $i++) {
            $t_array[$i] = $t[$i];
        }
        /** 搜索字符串长度初始化为t的长度 */
        $startLength = strlen($t);
        while ($startLength <= strlen($s)) {
            /** 滑块向右逐个字符移动 */
            for ($i = 0; $i <= strlen($s) - $startLength; $i++) {
                /** 获取搜索字符串 */
                $findArray = array_slice($s_array, $i, $startLength);
                /** 求两个集合的交集，如果并集等于t,则说明包含 */
                $jiaoji = array_intersect($findArray, $t_array);
                /** 如果交集元素个数等于 t数组个数 ，说明包含 */
                if (count($jiaoji) == strlen($t)) {
                    /** 如果结果集为空，则初始化 */
                    if (empty($back)) {
                        $back = $findArray;
                    }
                    /** 如果当前结果比结果集更小 ，则更新结果集 */
                    if ((count($findArray) < count($back))) {
                        $back = $findArray;
                    }
                }
            }
            /** 没有搜索到符合要求的字符串，那么变更搜索字符串长度 */
            $startLength++;
        }
        return implode("", $back);

    }

    /**
     * @param String[][] $board
     * @return Boolean
     * @link https://leetcode.cn/problems/valid-sudoku/?envType=study-plan-v2&envId=top-interview-150
     */
    function isValidSudoku($board)
    {

        /** 横向验证 */
        foreach ($board as $value) {
            $temp = [];
            foreach ($value as $v) {
                if (!isset($temp[$v])) {
                    $temp[$v] = 1;
                } else {
                    if ($v != ".") {
                        return false;
                    }
                }
            }
        }
        /** 纵向验证 */
        for ($i = 0; $i < count($board); $i++) {
            $value = array_column($board, $i);
            $temp = [];
            foreach ($value as $v) {
                if (!isset($temp[$v])) {
                    $temp[$v] = 1;
                } else {
                    if ($v != ".") {
                        return false;
                    }
                }
            }
        }
        /** 3*3的表格中是否重复 */
        for ($i = 0; $i < count($board); $i = $i + 3) {
            $end = $i;
            $temp = [];
            for ($m = $i; $m < $end; $end++) {
                for ($n = $i; $n < $end; $n++) {
                    $v = $board[$m][$n];
                    if (!isset($temp[$v])) {
                        $temp[$v] = 1;
                    } else {
                        if ($v != ".") {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * @param $board
     * @return bool
     * @link https://leetcode.cn/problems/valid-sudoku/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function isValidSudoku2($board)
    {
        // 记录某行，某位数字是否已经被摆放
        $row = array_fill(0, 9, array_fill(0, 9, false));
        // 使用 array_fill 函数初始化一个二维数组 $row，9 行 9 列，初始值都为 false，表示每行每个数字都未被摆放

        // 记录某列，某位数字是否已经被摆放
        $col = array_fill(0, 9, array_fill(0, 9, false));
        // 同样初始化一个二维数组 $col，用于记录每列数字的摆放情况

        // 记录某 3x3 宫格内，某位数字是否已经被摆放
        $block = array_fill(0, 9, array_fill(0, 9, false));
        // 初始化一个二维数组 $block，用于记录每个 3x3 宫格内数字的摆放情况

        for ($i = 0; $i < 9; $i++) {  // 外层循环遍历 9 行
            for ($j = 0; $j < 9; $j++) {  // 内层循环遍历 9 列
                if ($board[$i][$j] != '.') {  // 如果当前位置不是'.'（表示有数字）
                    /** - '1'表示计算ASCII值 */
                    $num = $board[$i][$j] - '1';  // 将当前数字转换为 0 - 8 的索引
                    $blockIndex = floor($i / 3) * 3 + floor($j / 3);  // 计算当前位置所属的 3x3 宫格的索引

                    if ($row[$i][$num] || $col[$j][$num] || $block[$blockIndex][$num]) {  // 如果在对应的行、列或宫格中已经存在该数字
                        return false;  // 则返回 false，表示数独不合法
                    } else {  // 否则
                        $row[$i][$num] = true;  // 将当前数字在当前行的对应位置标记为已存在
                        $col[$j][$num] = true;  // 在当前列的对应位置标记为已存在
                        $block[$blockIndex][$num] = true;  // 在当前宫格的对应位置标记为已存在
                    }
                }
            }
        }
        return true;  // 如果遍历完都没有发现不合法的情况，返回 true，表示数独合法
    }

    /**
     * @param Integer[][] $matrix
     * @return Integer[]
     * @link https://leetcode.cn/problems/spiral-matrix/?envType=study-plan-v2&envId=top-interview-150
     */
    public function spiralOrder($matrix)
    {
        if (empty($matrix) || empty($matrix[0])) {  // 如果矩阵为空或者矩阵的第一行空，返回空数组
            return [];
        }
        $res = [];  // 初始化结果数组
        $m = count($matrix);  // 获取矩阵的行数
        $n = count($matrix[0]);  // 获取矩阵的列数
        // 确定上下左右四条边的位置
        $up = 0;  // 上边初始位置为 0
        $down = $m - 1;  // 下边初始位置为行数减 1
        $left = 0;  // 左边初始位置为 0
        $right = $n - 1;  // 右边初始位置为列数减 1
        /** 这里是控制循环的圈数 */
        while (true) {  // 开始一个无限循环，直到内部条件满足退出
            /** 这里面是一个顺时针转一圈的代码   */
            /** 清理上边， 更新上边界 */
            for ($i = $left; $i <= $right; $i++) {  // 从左到右遍历上边的一行，将元素添加到结果数组
                $res[] = $matrix[$up][$i];
            }
            if (++$up > $down) {  // 上边向下移动一行，如果超过了下边，退出循环
                break;
            }
            /** 清理右边，更新右边界 */
            for ($i = $up; $i <= $down; $i++) {  // 从上到下遍历右边的一列，将元素添加到结果数组
                $res[] = $matrix[$i][$right];
            }
            if (--$right < $left) {  // 右边向左移动一列，如果小于左边，退出循环
                break;
            }
            /** 清理下边，更新下边界 */
            for ($i = $right; $i >= $left; $i--) {  // 从右到左遍历下边的一行，将元素添加到结果数组
                $res[] = $matrix[$down][$i];
            }
            if (--$down < $up) {  // 下边向上移动一行，如果小于上边，退出循环
                break;
            }
            /** 清理左边，更新左边界 */
            for ($i = $down; $i >= $up; $i--) {  // 从下到上遍历左边的一列，将元素添加到结果数组
                $res[] = $matrix[$i][$left];
            }
            if (++$left > $right) {  // 左边向右移动一列，如果超过右边，退出循环
                break;
            }
        }
        return $res;  // 返回螺旋遍历的结果数组
    }

    /**
     * 90旋转矩阵
     * @param $matrix
     * @return mixed
     * @link https://leetcode.cn/problems/rotate-image/description/?envType=study-plan-v2&envId=top-interview-150
     * @note 解题思路：原题是说逆时针旋转，在这里研究发现先上下翻转，在进行对角线翻转也可以达到效果
     * @note 这些逼人怎么想出来的，这个需要几何思维。找到了方法就很简单，找不到方法就抓瞎
     */
    public function rotate2($matrix)
    {
        $n = count($matrix);
        // 首先进行上下翻转
        for ($i = 0; $i < $n / 2; $i++) {
            list($matrix[$i], $matrix[$n - $i - 1]) = array($matrix[$n - $i - 1], $matrix[$i]);
        }
        // 然后进行对角线翻转
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i; $j < $n; $j++) {
                list($matrix[$i][$j], $matrix[$j][$i]) = array($matrix[$j][$i], $matrix[$i][$j]);
            }
        }
        return $matrix;
    }

    /**
     * @param Integer[][] $matrix
     * @return NULL
     * @link https://leetcode.cn/problems/set-matrix-zeroes/?envType=study-plan-v2&envId=top-interview-150
     * @note 暴力求解
     */
    function setZeroes($matrix)
    {

        /** 搜索数组中的0所在的坐标 */
        $search = [];
        foreach ($matrix as $x => $value) {
            foreach ($value as $y => $item) {
                if ($item == 0) {
                    $search[] = ['x' => $x, 'y' => $y];
                }
            }
        }

        foreach ($search as $value) {
            /** 横向全部变为零 y不变，x递增 */
            for ($i = 0; $i < count($matrix[0]); $i++) {
                if (isset($matrix[$i][$value['y']])){
                    $matrix[$i][$value['y']] = 0;
                }

            }
            /** 纵向全部变为零，x不变，y递增 */
            for ($i = 0; $i < count($matrix); $i++) {
                if (isset($matrix[$value['x']][$i])){
                    $matrix[$value['x']][$i] = 0;
                }

            }
        }

        return $matrix;
    }
}


$math = new Solution();
print_r($math->setZeroes([[1,1,1],[1,0,1],[1,1,1]]));
print_r($math->setZeroes([[0,1,2,0],[3,4,5,2],[1,3,1,5]]));







