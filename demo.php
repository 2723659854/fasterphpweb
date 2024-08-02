<?php

/**
 * 力扣算法题解法
 * @link https://leetcode.cn/studyplan/top-interview-150/
 */
class Solution {

    /**
     * @param Integer[] $nums1
     * @param Integer $m
     * @param Integer[] $nums2
     * @param Integer $n
     * @return NULL
     * @link https://leetcode.cn/problems/merge-sorted-array/description/?envType=study-plan-v2&envId=top-interview-150
     */
    function merge(array $nums1, int $m, array $nums2, int $n) {

        if ($n==0){
            return $nums1;
        }
        if ($n>0){
            for($i=0;$i<$n;$i++){

                $nums1[$m+$i]=$nums2[$i];
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
    function removeElement(array $nums, int $val) {

        $total = count($nums);
        foreach ($nums as $k=>$v){
            if ($v==$val){
                $nums[$k]=null;
                $total--;
            }
        }
        return ['total'=>$total,'array'=>$nums];
    }

    /**
     * @param array $nums
     * @return array
     * @link https://leetcode.cn/problems/remove-duplicates-from-sorted-array/?envType=study-plan-v2&envId=top-interview-150
     */
    function removeDuplicates(array $nums):array {
        $new=[];
        foreach ($nums as $value){
            if (!isset($new[$value])){
                $new[$value]=$value;
            }
        }
        return ['total'=>count($new),'array'=>$new];
    }

    /**
     * @param array  $nums
     * @return array
     * @link https://leetcode.cn/problems/remove-duplicates-from-sorted-array-ii/?envType=study-plan-v2&envId=top-interview-150
     */
    function removeDuplicates2(array $nums) {
        $new=[];
        foreach ($nums as $key=>$value){
            if (!isset($new[$value])){
                $new[$value]=1;
            }else{
                $new[$value]++;
                if ($new[$value]>2){
                    $nums[$key]=null;
                }
            }
        }
        foreach ($nums as $key=>$value){
            if ($value == null){
                unset($nums[$key]);
            }
        }
        return ['total'=>count($nums),'array'=>$nums];
    }


    /**
     * @param array $nums
     * @return int
     * @link https://leetcode.cn/problems/majority-element/?envType=study-plan-v2&envId=top-interview-150
     */
    function majorityElement(array $nums):int {
        $new=[];
        foreach ($nums as $value){
            if (!isset($new[$value])){
                $new[$value]=1;
            }else{
                $new[$value]++;
            }
        }
        $max = max($new);
        return array_search($max,$new);
    }

    /**
     * @param array $nums
     * @param int $k
     * @return array
     * @link https://leetcode.cn/problems/rotate-array/?envType=study-plan-v2&envId=top-interview-150
     */
    function rotate(array $nums, int $k):array {

        $length = count($nums);
        $partArray = array_slice($nums,$length-$k);
        $newArray = array_slice($nums,0,$length-$k);
        return array_merge($partArray,$newArray);

    }

    /**
     * @param Integer[] $prices
     * @return Integer
     * @note 这里用的动态规划，也可以硬算，但是太浪费时间了
     * @link https://leetcode.cn/problems/best-time-to-buy-and-sell-stock/?envType=study-plan-v2&envId=top-interview-150
     */
    function maxProfit(array $prices) {
        /** 假设第一天是在最低买入 */
        $min = $prices[0];
        /** 利润 */
        $profit = 0;
        /** 卖出 */
        for ($i=1;$i<count($prices);$i++){
            /** 卖出 */
            $temp = $prices[$i]-$min;
            /** 有利润 */
            if ($temp>0){
                $profit = max($profit,$temp);
            }else{
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
    function maxProfit2(array $prices) {

        if (count($prices)==0){
            return 0;
        }
        $profit = 0;
        for ($i=1;$i<count($prices);$i++){
            if ($prices[$i]>$prices[$i-1]){
                $profit += $prices[$i]-$prices[$i-1] ;
            }
        }
        return $profit;
    }

    /**
     * @param Integer[] $nums
     * @return Boolean
     * @link https://leetcode.cn/problems/jump-game/?envType=study-plan-v2&envId=top-interview-150
     */
    function canJump(array $nums) {
        $index = 0;
        $end = count($nums)-1;
        while($index<$end){
            $step = $nums[$index];
            $index = $index + $step;
            if ($index == $end){
                return true;
            }
            if ($step == 0){
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
    function jump(array $nums) {

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
}

#输入：nums1 = [1,2,3,0,0,0], m = 3, nums2 = [2,5,6], n = 3

$math = new Solution();
var_dump($math->jump([2,3,1,1,4]));
var_dump($math->jump([2,3,0,1,4]));
var_dump($math->jump([2, 3, 1, 1, 2, 4, 2, 0, 0]));
