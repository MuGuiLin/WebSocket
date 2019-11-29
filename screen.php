<?php

require __DIR__ . '/../vendor/autoload.php';
require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../../config/web.php');

(new yii\web\Application($config))->init();

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use app\models\Consult;

use app\models\Message;
use app\models\SendMessage;
use app\models\MpcMedalInfo;
use app\models\MpcVenueInfo;
use app\models\MpcWeather;
use app\models\MpcMediaData;
use app\models\MpcRproData;
use app\models\MpcRunData;
use app\models\MpcMpcData;
use app\models\MpcIbcData;
use app\models\MpcIbcCdtData;
use app\models\MpcXinhuaData;
use app\models\MpcXinhuaHotReport;
use app\models\MpcWxTrendData;
use app\models\MpcBaiduData;

\Workerman\Protocols\Http::header('Access-Control-Allow-Origin: *');
\Workerman\Protocols\Http::header('Access-Control-Allow-Headers: *');
\Workerman\Protocols\Http::header('Access-Control-Allow-Methods: GET, POST, PUT,OPTIONS');

//获取日期天气信息
function weather(){
    $data=MpcWeather::find()->select('id,sky, degree, wind, date')->orderBy('id desc')->asArray()->one();
    $weekarray=array("日","一","二","三","四","五","六");
    $data['date']= date("Y-m-d",time());
    $data['week']= "周".$weekarray[date("w",time())];
    return $data;
}
//获取奖牌信息
function medal(){
    $data=MpcMedalInfo::find()->select('rank, country,country_en, gold_medal,silver_medal,bronze_medal,total_medal')->where(['is_publish' => 1])->orderBy('rank asc')->asArray()->all();
    return $data;
}

//获取场馆信息
function venue(){
    $data=MpcVenueInfo::find()->select('image, address,lnglat, content, date')->where(['is_line' => 1,'date'=>date('Y-m-d',time())])->asArray()->all();
    if ($data){
        foreach ($data as $k=>$v){
//            $data[$k]['image']=Yii::$app->request->getHostInfo().$v['image'];
            $r=explode(',',$v['lnglat']);
            $data[$k]['value']=array($r[0],$r[1]);
        }
    }
    return $data;
}
//获取快讯信息
function news(){
    $data=Message::find()->select('id, content,raw_add_time')->where(['is_screen' => 1])->orderBy('raw_add_time desc')->asArray()->all();
    return $data;
}

//获取公告信息
function group(){
    $data=SendMessage::find()->select('id, title,content,image,raw_add_time')->where(['is_screen' => 1])->orderBy('raw_add_time desc')->asArray()->one();
    if ($data){
//        $data['image']=Yii::$app->request->getHostInfo().$data['image'];
        $data['content']=htmlspecialchars_decode($data['content']);
    }

    return array('0'=>$data);
}
//function screeninfo(){
//    $weather=weather();
//    $medal=medal();
//    $venue=venue();
//    $news=news();
//    $group=group();
//    $result=array('weather'=>$weather,'medal'=>$medal,'venue'=>$venue,'news'=>$news,'group'=>$group);
//    $post_data=json_encode($result);
//    return $post_data;
//}

//媒体信息数据发布
 function mediadata(){
    $r=array();
    $data=MpcMediaData::find()->select('id,media_agency_total, registered_reporter_total,
        overseas_media_num, china_media_num,date,up_time')->orderBy('id desc')->asArray()->one();
    array_push($r,array('name'=>'媒体机构总数','value'=>$data['media_agency_total']));
    array_push($r,array('name'=>'注册记者总数','value'=>$data['registered_reporter_total']));
    array_push($r,array('name'=>'境外媒体数量','value'=>$data['overseas_media_num']));
    array_push($r,array('name'=>'国内媒体数量','value'=>$data['china_media_num']));
    $post_data=json_encode(array('mediadata'=>$r));
    return $post_data;

}
//军运会进程数据发布
 function rprodata(){
    $data = MpcRproData::find()->select('date')->orderBy('id desc')->asArray()->one();
    $now=date('Y-m-d',time());
    $data['now']=$now;
    if ($now<$data['date']){
        $data['status']='1';
        $data['name']='倒计时';
        $data['day']=ceil((strtotime($data['date'])-time())/86400);
    }else{
        $data['status']='2';
        $data['day']=ceil((time()-strtotime($data['date']))/86400);
        $data['name']='开幕第';
    }
    if ($data['day']<10){
        $data['day']='0'.$data['day'];
    }
    $post_data=json_encode(array('process'=>$data));
     return $post_data;
 }
//实时记者人数发布
 function rundata(){
    $data = MpcRunData::find()->select('realtime_reporters')->orderBy('id desc')->asArray()->one();
    $post_data=json_encode(array('rundata'=>$data));
    return $post_data;
}
//MPC数据发布
 function mpcdata(){
    $data = MpcMpcData::find()->select('public_signal_broad_time, news_channel_broad_time,press_room_use_times,
         audio_system_use_num,audio_system_total')->orderBy('id desc')->asArray()->one();
    $post_data=json_encode(array('mpcdata'=>$data));
    return $post_data;
}

//ibc CDT数据发布
 function cdtdata(){
    $r=array();
    $data = MpcIbcCdtData::find()->select('id,signal_aggregation_num,signal_scheduling_num,
        signal_distribution_num,trans_duration_num,date,up_time')->orderBy('id desc')->asArray()->one();
    array_push($r,array('name'=>'信号汇聚','value'=>$data['signal_aggregation_num']));
    array_push($r,array('name'=>'信号调度','value'=>$data['signal_scheduling_num']));
    array_push($r,array('name'=>'信号分发','value'=>$data['signal_distribution_num']));
    array_push($r,array('name'=>'传输时长','value'=>$data['trans_duration_num']));
    $post_data=json_encode(array('cdtdata'=>$r));
     return $post_data;
 }
//ibc数据发布
 function ibcdata(){
    $data = MpcIbcData::find()->select('record_material_time,edit_room_use_num,edit_room_total,
         live_venue_num,studio_use_num,studio_total,single_point_use_num,single_point_total')
        ->orderBy('id desc')->asArray()->one();
    $post_data=json_encode(array('ibcdata'=>$data));
     return $post_data;
 }
//报道总量发布
 function xhdata(){
     $data = MpcXinhuaData::find()->select('total_reported')->orderBy('id desc')->asArray()->one();
     $data2 = MpcXinhuaHotReport::find()->select('title,read_pv,rank')
         ->orderBy('rank asc')->limit(5)->asArray()->all();
     $post_data=json_encode(array('xhdata'=>$data,'xharticle'=>$data2));
     return $post_data;
 }

//新华社热门文章推送
// function xharticle(){
//    $data = MpcXinhuaHotReport::find()->select('title,read_pv,rank')
//        ->orderBy('rank asc')->asArray()->all();
//    $post_data=json_encode(array('xharticle'=>$data));
//     return $post_data;
// }

//新榜微信数据推送
 function wxtrend(){
     $r=array();
     $num=array();
     $date=array();
     $data = MpcWxTrendData::find()->select('id,article_num,public_num,read_num,date,up_time')->orderBy('date desc')->asArray()->one();
     if ($data){
         $enddate=$data['date'];
         $startdate=date('Y-m-d',strtotime($enddate)-6*86400);
         $total=MpcWxTrendData::find()->select(['SUM(article_num) AS total_article_num,SUM(read_num) AS total_read_num'])
             ->where(' `date` between "'.$startdate.'" and "'. $enddate .'"')->asArray()->one();
         array_push($r,array('name'=>'篇数','value'=>$total['total_article_num']));
         array_push($r,array('name'=>'涉及公众号','value'=>$data['public_num']));
         array_push($r,array('name'=>'总阅读数','value'=>$total['total_read_num']));
         //article_num,
         $trend = MpcWxTrendData::find()->select('read_num as num,date')
             ->where(' `date` between "'.$startdate.'" and "'. $enddate .'"')
             ->orderBy('date asc')->asArray()->all();
         foreach ($trend as $k=>&$v){
             $v['date']=date('m-d',strtotime($v['date']));
             array_push($num,$v['num']);
             array_push($date,$v['date']);
         }
     }
     $post_data=json_encode(array('data'=>$r,'num'=>$num,'date'=>$date));
     return $post_data;
 }
//百度指数数据推送
 function baidudata(){
     $r=array();
     $num=array();
     $date=array();
     $data = MpcBaiduData::find()->select('date')->orderBy('date desc')->asArray()->one();
     if ($data){
         $enddate=$data['date'];
         $startdate=date('Y-m-d',strtotime($enddate)-6*86400);
         $total=MpcBaiduData::find()->select(['SUM(search_num) AS total_search_num,SUM(news_num) AS total_news_num'])
             ->where(' `date` between "'.$startdate.'" and "'. $enddate .'"')->asArray()->one();
         array_push($r,array('name'=>'日均搜索量','value'=>round($total['total_search_num']/7)));
         array_push($r,array('name'=>'日均资讯量','value'=>round($total['total_news_num']/7)));
         //news_num,
         $trend = MpcBaiduData::find()->select('search_num as num,date')
             ->where(' `date` between "'.$startdate.'" and "'. $enddate .'"')
             ->orderBy('date asc')->asArray()->all();
         foreach ($trend as $k=>&$v){
             $v['date']=date('m-d',strtotime($v['date']));
             array_push($num,$v['num']);
             array_push($date,$v['date']);
         }
     }
     $post_data=json_encode(array('data'=>$r,'num'=>$num,'date'=>$date));
     return $post_data;
}

function log_file($str,$force=false){
    if(!$force){
        return true;
    }
    $t = date("Y-m-d H:i:s");
    if (is_array($str)) {
        $str = "--- print array ---\n".var_export($str, true);
    }
    $_log_dir = 'log/';
    if(!file_exists($_log_dir)){
        mkdir($_log_dir, 0777, TRUE);
    }

    $_log_file = $_log_dir . date("d") . ".log";
    $str = '['.date("m-d H:i:s").'] '.$t.' '.$str."\n";
    error_log($str, 3, $_log_file);
}

$context = array(
    'ssl' => array(
        'local_cert' => $config['params']['local_cert'],
        'local_pk' => $config['params']['local_pk'],
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false, //如果是自签名证书需要开启此选项
    )
);

// listen port for socket.io client
//$io = new SocketIO(8443, $context);
$io = new SocketIO(8443);
/**
 *  滚动消息
 *  code: 0 没有数据，1 数据有变化  2 数据没变
 */
$io->on('connection', function ($socket) use ($io) {
//    log_file(screeninfo(),true);
    // 初始化数据消息，socket连接后 发送一次
//    $socket->emit('init',screeninfo());
//    log_file(json_encode(array('weather'=>weather())),true);
    $socket->emit('weather',json_encode(array('weather'=>weather())));

    $socket->emit('medal',json_encode(array('medal'=>medal())));

    $socket->emit('venue',json_encode(array('venue'=>venue())));

    $socket->emit('news',json_encode(array('news'=>news())));

    $socket->emit('group',json_encode(array('group'=>group())));

    $socket->emit('mediadata',mediadata());

    $socket->emit('process',rprodata());
//    log_file(rprodata(),true);

    $socket->emit('rundata',rundata());
//    log_file(rundata(),true);

    $socket->emit('mpcdata',mpcdata());


    $socket->emit('cdtdata',cdtdata());


    $socket->emit('ibcdata',ibcdata());


    $socket->emit('xhdata',xhdata());


    $socket->emit('wxtrend',wxtrend());


    $socket->emit('baidudata',baidudata());


    // 测试：聊天信息
    $socket->on('initOK', function ($msg) use ($io) {
//        log_file($msg,true);
        $io->emit('initOK',$msg);
    });

});


// 当$io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
$io->on('workerStart', function () {

    // 监听一个http端口
    $inner_http_worker = new Worker('http://127.0.0.1:2122');
    // 当http客户端发来数据时触发
    $inner_http_worker->onMessage = function ($http_connection, $data) {
        global $uidConnectionMap;
        $_POST = $_POST ? $_POST : $_GET;
        if(@$_POST['type']){
            global $io;
            $to = @$_POST['to'];
//            log_file(@$_POST['type'],true);
//            log_file(@$_POST['content'],true);
//            $_POST['content'] = @$_POST['content'];
            // 有指定uid则向uid所在socket组发送数据
            if ($to) {
                $io->to($to)->emit(@$_POST['type'], @$_POST['content']);
                // 否则向所有uid推送数据
            } else {
                $io->emit(@$_POST['type'], @$_POST['content']);
            }
            // http接口返回，如果用户离线socket返回fail
            if ($to && !isset($uidConnectionMap[$to])) {
                return $http_connection->send('offline');
            } else {
                return $http_connection->send('ok');
            }
        }
        // 推送数据的url格式 type=publish&to=uid&content=xxxx
//        switch (@$_POST['type']) {
//            case 'asr_service':
//                global $io;
//                $to = @$_POST['to'];
////                $info=screeninfo();
////                log_file($info,true);
//                log_file('post_to_screen:',true);
//                log_file(@$_POST['content'],true);
//                $_POST['content'] = htmlspecialchars(@$_POST['content']);
//                // 有指定uid则向uid所在socket组发送数据
//                if ($to) {
////                    $io->to($to)->emit('new_msg', $_POST['content']);
//                    $io->to($to)->emit('new_msg', @$_POST['content']);
//                    // 否则向所有uid推送数据
//                } else {
//                    $io->emit('new_msg', @$_POST['content']);
//                }
//                // http接口返回，如果用户离线socket返回fail
//                if ($to && !isset($uidConnectionMap[$to])) {
//                    return $http_connection->send('offline');
//                } else {
//                    return $http_connection->send('ok');
//                }
//            case 'traffic':
//                global $io;
//                $to = @$_POST['to'];
//                $_POST['content'] = htmlspecialchars(@$_POST['content']);
//                // 有指定uid则向uid所在socket组发送数据
//                if ($to) {
//                    $io->to($to)->emit('new_msg', $_POST['content']);
//                    // 否则向所有uid推送数据
//                } else {
//                    $io->emit('traffic', @$_POST['content']);
//                }
//                // http接口返回，如果用户离线socket返回fail
//                if ($to && !isset($uidConnectionMap[$to])) {
//                    return $http_connection->send('offline');
//                } else {
//                    return $http_connection->send('ok');
//                }
//            case 'radio':
//                global $io;
//                $to = @$_POST['to'];
//                $_POST['content'] = htmlspecialchars(@$_POST['content'], ENT_QUOTES);
//                // 有指定uid则向uid所在socket组发送数据
//                if ($to) {
//                    $io->to($to)->emit('new_msg', $_POST['content']);
//                    // 否则向所有uid推送数据
//                } else {
//                    $io->emit('radio', @$_POST['content']);
//                }
//                // http接口返回，如果用户离线socket返回fail
//                if ($to && !isset($uidConnectionMap[$to])) {
//                    return $http_connection->send('offline');
//                } else {
//                    return $http_connection->send('ok');
//                }
//            case 'asr_switch':
//                global $io;
//                $to = @$_POST['to'];
//                $_POST['content'] = htmlspecialchars(@$_POST['content'], ENT_QUOTES);
//                // 有指定uid则向uid所在socket组发送数据
//                if ($to) {
//                    $io->to($to)->emit('new_msg', $_POST['content']);
//                    // 否则向所有uid推送数据
//                } else {
//                    $io->emit('asr_switch', @$_POST['content']);
//                }
//                // http接口返回，如果用户离线socket返回fail
//                if ($to && !isset($uidConnectionMap[$to])) {
//                    return $http_connection->send('offline');
//                } else {
//                    return $http_connection->send('ok');
//                }
//            case 'live':
//                global $io;
//                $to = @$_POST['to'];
//                $_POST['content'] = htmlspecialchars(@$_POST['content'], ENT_QUOTES);
//                // 有指定uid则向uid所在socket组发送数据
//                if ($to) {
//                    $io->to($to)->emit('new_msg', $_POST['content']);
//                    // 否则向所有uid推送数据
//                } else {
//                    $io->emit('live', @$_POST['content']);
//                }
//                // http接口返回，如果用户离线socket返回fail
//                if ($to && !isset($uidConnectionMap[$to])) {
//                    return $http_connection->send('offline');
//                } else {
//                    return $http_connection->send('ok');
//                }
//        }

        return $http_connection->send('fail');
    };

    // 执行监听
    $inner_http_worker->listen();
});

Worker::runAll();