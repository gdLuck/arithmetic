<?php
/**
 * 基础排序算法
 */

/* 交换 */
function swap($arr, $i, $j)
{
    $temp    = $arr[$i];
    $arr[$i] = $arr[$j];
    $arr[$j] = $temp;
    return $arr;
}

/* 冒泡排序法 将每位与整个数组比较，最大的放最后；*/
class Bubble 
{
    public static function bubbleSort(array $arr):array
    {
        $count = count($arr);
        if (empty($arr) || 0 == $count ) return [];
        for ($i=0; $i < $count-1; $i++){ //该层循环控制 需要冒泡的轮数
            for ($j=$count-1; $j>$i; $j--){ //该层循环用来控制每轮 冒出一个数 需要比较的次数
                if ($arr[$j] < $arr[$j-1]){
                    $arr = swap($arr, $j-1, $j);
                }
            }
        }
        return $arr;
    }
}

/* 选择 在要排序的一组数中，选出最小的一个数与第一个位置的数交换。然后在剩下的数当中再找最小的与第二个位置的数交换，如此循环到倒数第二个数和最后一个数比较为止。 */
class select
{
    public static function selectSort(array $arr)
    {
        $count = count($arr);
        if (empty($arr) || 0 == $count ) return [];
        
        $minIndex = 0;
        for ($i=0; $i < $count-1; $i++){// 遍历一到倒二位
            $minIndex = $i;
            for ($j=$i+1; $j<$count; $j++){ //遍历后一位到未尾
                if ($arr[$minIndex] > $arr[$j]){
                    $minIndex = $j; //取最小值
                }
            }
            //最小值不为记录值 交换
            if ($minIndex != $i) $arr = swap($arr, $i, $minIndex);
        }
        return $arr;
    }
}

/* 插入排序 在要排序的一组数中，假设前面的数已经是排好顺序的，现在要把第n个数插到前面的有序数中，使得这n个数也是排好顺序的。如此反复循环，直到全部排好顺序。*/
class insert
{
    public static function insertSort(array $arr)
    {
        $count = count($arr);
        if (empty($arr) || 0 == $count ) return [];
        
        for ($i=1; $i < $count; $i++){
            $j = $i;
            $target = $arr[$i];
            
            //后移 发现插入的元素要小，交换位置，将后边的元素与前面的元素互换
            while ($j >0 && $target < $arr[$j-1]){
                $arr[$j] = $arr[$j-1];
                $j--;
            }
            
            //插入
            $arr[$j] = $target;
            //echo json_encode($arr)."<br/>";
        }
        return $arr;
    }
}

/*
 * 快速排序
选择一个基准元素，通常选择第一个元素或者最后一个元素。
通过一趟扫描，将待排序列分成两部分，一部分比基准元素小，一部分大于等于基准元素。
此时基准元素在其排好序后的正确位置，然后再用同样的方法递归地排序划分的两部分。
*/
class quick
{
    public static function quickSort(array $arr):array
    {
        $count = count($arr);
        if ($count <= 0) return $arr;

        $baseNum = $arr[0]; //取第一个数做基准
        $leftArr = []; //小于基准
        $rightArr = []; //大于等于基准
        for ($i=1; $i < $count; $i++){
            if ($baseNum > $arr[$i]){
                $leftArr[] = $arr[$i];
            }else{
                $rightArr[] = $arr[$i];
            }
        }
        $leftArr = self::quickSort($leftArr);
        $rightArr = self::quickSort($rightArr);

        return array_merge($leftArr, [$baseNum], $rightArr);
    }
}

/**
 * 查找
 */
class search
{
    /* 二分查找 */
    public static function binarySearch(array $arr, $target)
    {
        if (empty($arr)) return false;
        $low = 0;
        $high = count($arr) - 1;

        while ($low <= $high){
            $mid = floor(($low+$high)/2);
            if ($arr[$mid] == $target) return $mid;
            if ($arr[$mid] > $target) $high = $mid -1;
            else $low = $mid + 1;
        }
        return false;
    }

    /* 顺序查找 */
    public static function seqSearch(array $arr, $target)
    {
        foreach ($arr as $k => $v){
            if ($v == $target)
                return $k;
        }
        return false;
    }
}


echo "<pre>";

$i = 0;
$arr = [];
while ($i < 10){
    $arr[] = mt_rand(1,100);
    $i++;
}
echo 'arr:';
prjson($arr);
echo PHP_EOL;

$result = Bubble::bubbleSort($arr);
prjson($result);
$result = select::selectSort($arr);
prjson($result);
$result = insert::insertSort($arr);
prjson($result);
$result = quick::quickSort($arr);
prjson($result);

echo 'search 20:';
var_dump(search::binarySearch($result, 20));
echo "</pre>";

function prjson($val){
    echo json_encode($val).PHP_EOL;
}

