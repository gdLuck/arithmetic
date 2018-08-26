<?php
/**
 * 抽奖算法
 *
一、固定中奖
 若达到指定次数则必中奖 例：首抽中二奖，第十次必中大奖等
二、 计数器 (保底中奖模型)
 每次抽奖失败计数统计，当次数达到一定值则下次判定100%中奖，中奖后重置计数器
三、 随机步长模式 (保底中奖模型)
 去掉了独立随机事件，并把计数增长改为随机量，最终在累计超过阈值时得奖。这种模型如果有个较大的阈值和较小的步长下限，还可以起到让玩家在头几次抽奖必然不中（大）奖的效果

- 整合测试

用户
> 用户ID | 连续不中次数
奖品与规则配置
> 奖品ID | 奖品 | 权重 | 区间 | 库存(为0则返回未中奖) | 奖品阈值 | 步长区间 | 首抽品 | 十连品
用户大奖步长与连续不中计数
> 用户ID | 奖品ID | 步长累加值
奖品记录
> 用户ID | 奖品ID | 其他信息
领取统计
> 用户ID | 奖励ID
 * author: gtb
 * Date: 2018/3/28
 * Time: 18:35
 */

class roulette
{
    /**
     * @return roulette
     */
    public static function factory()
    {
        $class = __CLASS__;
        return new $class();
    }

    /**
     * 配置表
     * mt_rand(min,max) =》 包括min与max
     * 未中奖权重计算：20% 则 抽10次中2次 X表示未中奖数 N表示奖品数 则X/N=8/2 ==> X=N*4
     * 则 10% =》 X=8110*9=72990 总区间1-81100
     * 阈值可选方案：一、全部用户统一累加值 二、单用户分别记录累加值（不想送）
     */
    private $config = [
        ['id' => 1, 'name' => '1元', 'power' => 5000, 'range' => [0, 5000], 'stock' => 5000, 'doorsill' => 0, 'stepSize' => [], 'isOne' => 0, 'isTen' => 0],
        ['id' => 2, 'name' => '2元', 'power' => 2000, 'range' => [5000, 7000], 'stock' => 2000, 'doorsill' => 0, 'stepSize' => [], 'isOne' => 0, 'isTen' => 0],
        ['id' => 3, 'name' => '3元', 'power' => 500, 'range' => [7000, 7500], 'stock' => 500, 'doorsill' => 0, 'stepSize' => [], 'isOne' => 0, 'isTen' => 0],
        ['id' => 4, 'name' => '5元', 'power' => 500, 'range' => [7500, 8000], 'stock' => 500, 'doorsill' => 0, 'stepSize' => [], 'isOne' => 0, 'isTen' => 0],
        //累积命中最多两次便送
        ['id' => 5, 'name' => '手表', 'power' => 100, 'range' => [8000, 8100], 'stock' => 50, 'doorsill' => 100, 'stepSize' => [50, 110], 'isOne' => 1, 'isTen' => 1],
        //最少2次最多5次
        ['id' => 6, 'name' => '苹果手机', 'power' => 10, 'range' => [8100, 8110], 'stock' => 5, 'doorsill' => 1000, 'stepSize' => [200, 500], 'isOne' => 0, 'isTen' => 1],
        //-1 无限
        ['id' => 7, 'name' => '谢谢参与', 'power' => 72990, 'range' => [8110, 81100], 'stock' => -1, 'doorsill' => 0, 'stepSize' => [], 'isOne' => 0, 'isTen' => 0],
    ];

    private $configKey = 'test::roulette_config';
    
    /**
     * @param $user 抽奖用户信息
     * //测试用 不考虑并发
     */
    public function run($user):array
    {
        try {
            $_config = $this->getConfig();
            // 生成随机值
            $randNum = mt_rand(1, 81100);
            //if($randNum > 8100 && $randNum <= 8110) echo $randNum.PHP_EOL;
            // 判断是否首抽
            //...
            // 判断是否连续十次不中
            //...
            // 判断正常抽法
            $key = $this->awardSearch($_config, $randNum);
            if ($key == -1) return [];

            $award = $_config[$key];
            //验证库存（可直接更新数据库表，判断库存大于0才更新，否则返回未抽中）
            if ($award['stock'] > 0) {
                // 验证阈值
                if ($award['doorsill'] > 0) {
                    if (!$this->checkStepSize($award, $user)) return [];
                }
                //更新库存
                $_config[$key]['stock'] -= 1;
                db::getRedis()->set($this->configKey, json_encode($_config));
                return $award;
            }
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }

        return [];
    }

    private function getConfig(): array
    {
        $redis = db::getRedis();
        if (!$config = $redis->get($this->configKey)) {
            $redis->set($this->configKey, json_encode($this->config));
            return $this->config;
        }
        return json_decode($config, true);
    }
    
    /**
     * 检查步长累计是否达标
     */
    public function checkStepSize($award, $user)
    {
        $stepSize = mt_rand($award['stepSize'][0], $award['stepSize'][1]);
        if ($stepSize >= $award['doorsill']) {
            return true;
        }
        $stepSizeKey = 'test::stepSize_' . $user['uid'] . '_' . $award['id'];
        $newValue = intval(db::getRedis()->get($stepSizeKey)) + $stepSize;
        if ( $newValue >= $award['doorsill']){
            db::getRedis()->set($stepSizeKey, 0); //重置
            return true;
        }
        db::getRedis()->set($stepSizeKey, $newValue);
        return false;
    }

    /**
     * 查询中奖情况
     * @param $configArr 奖品列表
     * @param $randNum
     * @return int
     */
    private function awardSearch(array $configArr, int $randNum):int
    {
        if (empty($configArr)) return -1;

        $low = 0;
        $high = count($configArr)-1;

        while ($low <= $high){
            $mid = floor(($low + $high)/2);
            if ($configArr[$mid]['range'][0] < $randNum && $randNum <= $configArr[$mid]['range'][1]) return $mid;
            elseif ($configArr[$mid]['range'][1] < $randNum) $low = $mid + 1;
            else $high = $mid -1;
        }
        return -1;
    }

    //首次单抽
    private function oneDraw()
    {

    }
    //十连抽
    private function tenDraw()
    {

    }
}

class db
{
    public static function getRedis()
    {
        static $redis = null;
        if ($redis != null){
            return $redis;
        }

        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->auth('aaa123');

            if ($redis->ping() != '+PONG')
                throw new Exception('redis 连接失败！');
        }catch (Exception $e){
            throw new Exception('redis 连接失败！');
        }

        return $redis;
    }
}

function pr($arr, $is = true)
{
    echo json_encode($arr).PHP_EOL;
    if ($is) exit;
}

echo "<pre>";
// 用户与未中奖数
$user = [
    'uid' => 1,
    'num' => 0,
    'name' => 'gtb'
];

//验证用户抽奖权限

//抽奖
$error = 0;
$run   = 10000;
$redis = db::getRedis();
$hashKey = 'test::usersAwardToHash'; //统计总数
$listKey = 'test::userAwardList_'.$user['uid']; //记录用户领取信息

for ($i=1; $i<=$run; $i++){
    $award = roulette::factory()->run($user);
    if (!empty($award)){
        //领取记录
        $redis->hIncrBy($hashKey, $user['uid'], 1);
        $redis->rPush($listKey, $award['id']);
    }else {
        $error++;
    }
}

$count = $redis->lLen($listKey);
$result = $redis->lRange($listKey, 0, -1);

$info = array_count_values($result);

echo '本轮抽奖次数：'. $run.PHP_EOL;
echo '本轮未中奖数：'. $error.PHP_EOL;
echo '总中奖数：'. $count.PHP_EOL;
echo '总中奖详情：'.PHP_EOL;
ksort($info);
foreach ($info as $k => $v){
    switch ($k){
        case '1':
            echo '1元：'.$v.PHP_EOL;
            break;
        case '2':
            echo '2元：'.$v.PHP_EOL;
            break;
        case '3':
            echo '3元：'.$v.PHP_EOL;
            break;
        case '4':
            echo '5元：'.$v.PHP_EOL;
            break;
        case '5':
            echo '手表：'.$v.PHP_EOL;
            break;
        case '6':
            echo '苹果手机：'.$v.PHP_EOL;
            break;
        default:
            echo '谢谢参与';
            break;
    }
}

//重置，慎用
//$redis->flushDB();

/**
 * 测试结果，除阈值外其他奖励10%中奖率基本正常
 *
本轮抽奖次数：81100
本轮未中奖数：73069
总中奖数：8031
总中奖详情：
1元：4994
2元：2000
3元：500
5元：485
手表：50
苹果手机：2
 */

echo "</pre>";
