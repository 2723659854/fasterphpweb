<?php
/**
 * 动态规划的作用：
 * 只是为了减少重复计算而已 ，降低时间复杂度
 * @link  https://www.hello-algo.com/chapter_dynamic_programming/knapsack_problem/#4
 * @link  https://zh-v1.d2l.ai/chapter_deep-learning-basics/linear-regression.html
 */
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

    /** 动态规划求解 指的是把 0 ~ i 这些物品选择性的放进容量是 w 的背包中*/
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


/** 案例三：记忆优化算法 */

// 定义一个数组来存储已经计算过的斐波那契数列的值
$cache = [];

function fibonacci($n)
{
    global $cache;

    // 如果已经计算过，直接返回缓存中的值
    if (isset($cache[$n])) {
        return $cache[$n];
    }

    // 计算斐波那契数列的值
    if ($n <= 1) {
        $result = $n;
    } else {
        $result = fibonacci($n - 1) + fibonacci($n - 2);
    }

    // 将计算结果存入缓存
    $cache[$n] = $result;
    return $result;
}
/** 在上面的例子中，fibonacci 函数通过记忆化数组 $cache 存储了已经计算过的斐波那契数列的值，避免了重复计算，从而提高了计算效率。 */
// 测试输出
$n = 10;
echo "斐波那契数列第 $n 项的值是：" . fibonacci($n) . "\n";

/** 案例4：倒序遍历算法 */

// 示例数组
$numbers = [1, 2, 3, 4, 5, 3, 6, 7, 3, 8];

// 需要删除的元素
$target = 3;

// 倒序遍历数组，删除指定元素
for ($i = count($numbers) - 1; $i >= 0; $i--) {
    if ($numbers[$i] === $target) {
        array_splice($numbers, $i, 1); // 删除元素
    }
}

// 输出删除后的数组
print_r($numbers);


/** 暴力搜索算法，就是穷举法 */
function threeSumClosest($nums, $target) {
    $n = count($nums);
    if ($n < 3) return null;

    $closestSum = PHP_INT_MAX;
    $minDiff = PHP_INT_MAX;

    for ($i = 0; $i < $n - 2; $i++) {
        for ($j = $i + 1; $j < $n - 1; $j++) {
            for ($k = $j + 1; $k < $n; $k++) {
                $sum = $nums[$i] + $nums[$j] + $nums[$k];
                $diff = abs($sum - $target);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closestSum = $sum;
                }
            }
        }
    }

    return $closestSum;
}

// 示例用法 暴力搜索算法会遍历所有可能的三元组 (nums[i], nums[j], nums[k]) 组合，计算它们的和，并找到最接近目标值 target 的和。
$nums = [-1, 2, 1, -4];
$target = 1;
$result = threeSumClosest($nums, $target);
echo "Closest sum to target $target is: $result\n";

/** 分治算法 之 归并排序 */

// 归并排序的实现函数
function merge_sort($arr)
{
    // 如果数组长度小于等于1，直接返回
    $length = count($arr);
    if ($length <= 1) {
        return $arr;
    }

    // 计算中间位置
    $mid = (int)($length / 2);

    // 分解：分别对左右两部分进行归并排序
    $left = merge_sort(array_slice($arr, 0, $mid));
    $right = merge_sort(array_slice($arr, $mid));

    // 解决：合并已排序的左右两部分
    return merge($left, $right);
}

// 合并两个已排序数组
function merge($left, $right)
{
    $result = [];
    $i = $j = 0;

    // 比较左右两部分的元素，依次放入结果数组
    while ($i < count($left) && $j < count($right)) {
        if ($left[$i] <= $right[$j]) {
            $result[] = $left[$i++];
        } else {
            $result[] = $right[$j++];
        }
    }

    // 将剩余的元素放入结果数组
    while ($i < count($left)) {
        $result[] = $left[$i++];
    }
    while ($j < count($right)) {
        $result[] = $right[$j++];
    }

    return $result;
}

// 示例用法
$arr = [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5];
$sorted_arr = merge_sort($arr);
echo "Original array: " . implode(", ", $arr) . "\n";
echo "Sorted array: " . implode(", ", $sorted_arr) . "\n";
