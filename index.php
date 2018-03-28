<?php
/**
 * 迭代
 * @param unknown $n
 */
function sum($n){
    $s = 0;
    for ($i= 1;$i <= $n;){
        $s += $i;
        $i++;
    }
    return $s;
}

echo sum(100).'<br/>';

/**
 *  递归
 */
function other($n,$s = 0){
    if ($n >=1){
        $s += $n;
        $s = other(--$n, $s);
    }
    return $s;
}

echo other(100);