<?php
/** 动态规划的作用：只是为了减少重复计算而已 ，降低时间复杂度 */
/**
 * 案例1
 * 动态规划算法
 * @param array $coins 零钱数组
 * @param int $amount 总金额
 * @return int|mixed
 * @comment 计算出凑出指定金额所需要的最少的纸币数，比如凑出14元的人民币，最少需要2张7元的人民币
 */
function minCoins(array $coins, int $amount)
{
    /** 创建一个以金额为长度的数组，填充php的最大值 */
    $dp = array_fill(0, $amount + 1, PHP_INT_MAX);
    /** 凑出0元的金额需要0张纸币 */
    $dp[0] = 0;
    /** 依次计算凑出0到amount金额需要的纸币数 */
    for ($i = 1; $i <= $amount; $i++) {
        /** 遍历零钱 */
        foreach ($coins as $coin) {
            /** 如果当前需要凑出的金额大于等于当前的零钱面额 并且 （如果使用当前面额，剩余金额的数据已经有值）*/
            if ($i - $coin >= 0 && $dp[$i - $coin] != PHP_INT_MAX) {
                /** 由于我们从金额 i - $coin 加上一个面额为 $coin 的纸币，就可以凑出金额 i，所以凑出金额 i 所需的纸币数就是凑出金额 i - $coin 所需的最少纸币数再加上这一个 $coin 的纸币，即 1。*/
                $dp[$i] = min($dp[$i], $dp[$i - $coin] + 1);
            }
        }
    }
    /** 如果$dp[$amount]=PHP_INT_MAX 说明没有凑出所需的金额，如果有则返回所需的零钱个数 */
    return $dp[$amount] == PHP_INT_MAX ? -1 : $dp[$amount];
}

$coins = [1, 2, 5, 7, 10];
$amount = 14;
$result = minCoins($coins, $amount);
echo "凑出 " . $amount . " 元所需的最少纸币数是: " . $result;
echo "\r\n";

/** 案例2：动态规划，将商品放入到背包中，使得包中的商品价值最高 */

/** 商品信息 */
$goods = [
    ['id' => 1, 'weight' => 10, 'price' => 50],
    ['id' => 2, 'weight' => 20, 'price' => 120],
    ['id' => 3, 'weight' => 30, 'price' => 150],
    ['id' => 4, 'weight' => 40, 'price' => 210],
    ['id' => 5, 'weight' => 50, 'price' => 240],
];
/** 背包最大承重 */
$maxWeight = 60;

function knapsack($goods, $maxWeight) {
    /** 物品的数量 */
    $n = count($goods); //
    /** 初始化dp数组，dp[i][w]表示前i个物品，在容量w下的最大价值 */
    $dp = array_fill(0, $n + 1, array_fill(0, $maxWeight + 1, 0)); //

    /** 动态规划求解 */
    for ($i = 1; $i <= $n; $i++) {
        for ($w = 1; $w <= $maxWeight; $w++) {
            if ($goods[$i - 1]['weight'] <= $w) {
                /** 如果当前物品的重量小于等于当前容量w，则可以选择放入背包 */
                $dp[$i][$w] = max(
                    /** 不放入当前物品，保持之前的最大价值 */
                    $dp[$i - 1][$w],
                    /** 放入当前物品，更新最大价值 = 上一个商品[当前重量 - 上一个商品放入后重量 ]的价格 + 上一个商品的价格 */
                    $dp[$i - 1][$w - $goods[$i - 1]['weight']] + $goods[$i - 1]['price']
                );
            } else {
                /** 如果当前物品的重量大于当前容量w，则无法放入背包，保持之前的最大价值 */
                $dp[$i][$w] = $dp[$i - 1][$w];
            }
        }
    }

    /** 回溯找出被选中的商品 */
    $selectedItems = [];
    /** 最大价值存储在dp[n][maxWeight]中 */
    $totalPrice = $dp[$n][$maxWeight];
    $remainingWeight = $maxWeight;

    /** 倒序搜索 */
    for ($i = $n; $i > 0 && $totalPrice > 0; $i--) {
        /** 如果dp[i][$remainingWeight]大于dp[i-1][$remainingWeight]，则说明第i个物品被选中 */
        if ($dp[$i][$remainingWeight] != $dp[$i - 1][$remainingWeight]) {
            /** 将选中的物品加入到结果数组中 */
            $selectedItems[] = $goods[$i - 1];
            /** 更新剩余容量和总价值 */
            $remainingWeight -= $goods[$i - 1]['weight'];
            $totalPrice -= $goods[$i - 1]['price'];
        }
    }

    /** 返回最大价值和选中的商品数组 */
    return ['maxPrice' => $dp[$n][$maxWeight], 'selectedItems' => $selectedItems];
}

// 调用函数求解背包问题
$result = knapsack($goods, $maxWeight);

// 输出结果
echo "最大总价值: " . $result['maxPrice'] . "\n";
echo "选中的商品: \n";
foreach ($result['selectedItems'] as $item) {
    echo "ID: " . $item['id'] . ", 重量: " . $item['weight'] . ", 价格: " . $item['price'] . "\n";
}





