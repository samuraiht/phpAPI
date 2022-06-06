<?php
# composerの読み込み
require_once '../vendor/autoload.php';

# 必要なモジュール、パッケージの読み込み
use GuzzleHttp\Client;

# 必要なfunction
function getArrayValue($data, $path) {
	$items = $data;
	foreach($path as $dir) $items = $items[$dir];# $data['results']['shop'] : $data['name'], $data['open']
	return $items;
}

function setData($item = NULL) {
	$row = [];
	foreach(OutputConfig as $col) $row[] = $item ? getArrayValue($item, $col['key']) : $col['csv'];
	return $row;
}

# -----------------設定--------------------
# 基本設定
const Debug = false;# レスポンスデータをvar_dump

# CSV設定
const OutputFile = 'output.csv';
const csvEnc = 'SJIS';# Shift-JIS

# API設定
const RequestMethod = 'GET';
const RequestURI = 'http://webservice.recruit.co.jp/hotpepper/gourmet/v1/';
const RequestQuery = [
	'query' => [
		"key" => 'df139253fc582213',
		'count' => 100,
		'large_area' => 'Z011',
		'keyword' => '渋谷駅',
		'format' => 'json'
	]
];

# 結果一覧の配列の階層
const ItemListDir = ['results', 'shop'];

# 出力したい内容、CSVの見出し
const OutputConfig = [
	['key' => ['name'], 'csv' => '名称'],
	['key' => ['open'], 'csv' => '営業日'],
	['key' => ['address'], 'csv' => '住所'],
	['key' => ['access'], 'csv' => 'アクセス']
];
# -----------/設定------------

# -----------リクエスト------------
# HTTPリクエスト用のクラスインスタンスを生成(GuzzleHttp\Client)
$client = new Client();

# try { 処理 }
# catch(Exception $e) { 例外処理(エラー発生時の処理) }
# finally { エラーが出たかどうかに関係なく、tryまたはcatchの終了後に実行する処理 }
try {
# GuzzleHttp/Clientインスタンスを使って、APIのURLにアクセスしてresponse bodyを取得
	$json = $client->request(RequestMethod, RequestURI, RequestQuery)->getBody();
} catch(Exception $e) {# 例外処理(エラーException時の処理)
	var_dump($e);
	exit;# functionを即終了するのはreturn、それ以外で即終了はexit
}
# -----------/リクエスト------------

# -----------レスポンス解析------------
# json_decode(string JSON形式の文字列, bool 連想配列にするかどうか)
# 戻り値はstdClassの塊または連想配列(第二引数=trueのとき)
$response = json_decode($json, true);

# 受け取ったデータの確認
if(Debug) var_dump($response);

# 必要なデータ(結果一覧の配列)を取り出す
# $items = $response['results']['shop']
$items = getArrayValue($response, ItemListDir);
# -----------/レスポンス解析------------

# -----------------CSV書き込み--------------------
# CSV用出力データ
$outputData = [];

# 見出し行
$outputData[] = setData();# ['名称', '営業日', '住所', 'アクセス']

# データ行
foreach($items as $item) $outputData[] = setData($item);

$phpEnc = mb_internal_encoding();
if(csvEnc != $phpEnc) $outputData = mb_convert_encoding($outputData, csvEnc, $phpEnc);

# CSVファイルを開くまたは新規作成
$handle = fopen(OutputFile, 'w');

# 中身を書き込む
foreach($outputData as $item) fputcsv($handle, $item);

# CSVファイルを閉じる
fclose($handle);
# -----------------/CSV書き込み--------------------
?>