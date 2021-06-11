<?php
header('Content-Type:text/plain;charset=utf-8');
require 'vendor/autoload.php';
use Medoo\medoo;
require_once 'Medoo-1.7.10/src/Medoo.php';
ini_set('date.timezone','Asia/Shanghai');


//连接数据库
global $database;
$database =new medoo([
    'database_type' => 'mysql',
    'database_name' => 'cointelegraphcn',
    'server' => '127.0.0.1',
    'port' => '3306',
    'username' => 'root',
    'password' => '*',
    'charset' => 'utf8'
]);




function secondrequest($url)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

}




function firstrequest($currenturl){
    $curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $currenturl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => '{"operationName":"MainPagePostsQuery","variables":{"place":"index","offset":0,"length":27,"short":"cn","cacheTimeInMS":1000},"query":"query MainPagePostsQuery($short: String, $offset: Int!, $length: Int!, $place: String = \\"index\\") {\\n  locale(short: $short) {\\n    posts(order: \\"postPublishedTime\\", offset: $offset, length: $length, place: $place) {\\n      data {\\n        cacheKey\\n        id\\n        slug\\n        postTranslate {\\n          cacheKey\\n          id\\n          title\\n          avatar\\n          published\\n          publishedHumanFormat\\n          leadText\\n          __typename\\n        }\\n        category {\\n          cacheKey\\n          id\\n          slug\\n          categoryTranslates {\\n            cacheKey\\n            id\\n            title\\n            __typename\\n          }\\n          __typename\\n        }\\n        author {\\n          cacheKey\\n          id\\n          slug\\n          authorTranslates {\\n            cacheKey\\n            id\\n            name\\n            __typename\\n          }\\n          __typename\\n        }\\n        postBadge {\\n          cacheKey\\n          id\\n          label\\n          postBadgeTranslates {\\n            cacheKey\\n            id\\n            title\\n            __typename\\n          }\\n          __typename\\n        }\\n        __typename\\n      }\\n      postsCount\\n      hasMorePosts\\n      __typename\\n    }\\n    __typename\\n  }\\n}\\n"}',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$response = curl_exec($curl);
curl_close($curl);


$html=json_decode($response, TRUE);
   // var_dump($html["data"]["locale"]['posts']['data']);die;
    $data = $html["data"]["locale"]['posts']['data'];
    foreach ($data as $value){
        $isnews=$value["category"]["slug"];
        $id=$value['postTranslate']['id'];
        $title = $value['postTranslate']['title'];
        $id=$value['postTranslate']['id'];
        $leadText=$value['postTranslate']['leadText'];
        $author=$value['author']['authorTranslates'][0]['name'];
        $published=$value['postTranslate']["publishedHumanFormat"];
        $linkrightpart=$value["slug"];
        $linkleftpart='https://cointelegraphcn.com/news/';
        $link=$linkleftpart.$linkrightpart;
        $indeedpage=secondrequest($link);
        preg_match_all('#<div class="post-content post-content_asia".*?>([\s\S]*?)<hr>#', $indeedpage, $out1);
        $content=$out1[1];
        if(!$content){
            preg_match_all('#<div class="post-content post-content_asia".*?>([\s\S]*?)</div>#', $indeedpage, $out2);
            $content=$out2[1];
        }

        echo '开始写入数据库。。。'.PHP_EOL;
        $dbdata['news_id']=$id;
        $dbdata['title']=$title;
        $dbdata['leadtext']=$leadText;
        $dbdata['author']=$author;
        $dbdata['publish_time']=$published;
        $dbdata['link']=$link;
        $dbdata['content']=$content;
        $dbdata['add_time']=time();
        $GLOBALS['database']->insert('cointelegraph_data', $dbdata);
        $error = $GLOBALS['database']->error();
        if($error[0] != "00000"){
            //var_dump($error[2]);
            echo "没有最新资讯。。。".PHP_EOL;
            exit();
        }

        echo $title."  写入成功".PHP_EOL;

    }

    echo "采集完成";

}
firstrequest('https://conpletus.cointelegraphcn.com/v1/');