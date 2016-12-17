<?php

require_once 'raderchart.php';

// 表示すべきデータです
$data = array(
    'HP'     => 60,
    '攻撃'   => 45,
    '防御'   => 50,
    '特殊'   => 80,
    '素早さ' => 70,
);

// 描画先のイメージを生成します
$image = imagecreatetruecolor(300, 300);
if (function_exists('imageantialias')) imageantialias($image, true);
$background = imagecolorallocate($image, 0xee, 0xee, 0xee);
imagefill($image, 0, 0, $background);

// 半径を指定してレーダーチャート描画クラスを生成します(必須)
$chart = new RaderChart(140);

// 軸の色を設定します(任意)
$chart->SetGraphAxis(0, 0, 0, 0);

// グラフ本体の塗り潰しと線の色を設定します(任意)
$chart->SetGraphFill(0xcc, 0xcc, 0xff, 64);
$chart->SetGraphLine(0, 0, 0, 32);

// TrueTypeフォントを設定します(フォントファイルがあれば)
//$chart->SetFont('butterfree.ttf', 16, 5, 0, 0, 0);

// 補助線を設定します(任意)
$chart->AddGuide(50, 0x44, 0x44, 0x44);
$chart->AddGuideFill(100, 0x88, 0x88, 0x88, 0, 0xff, 0xff, 0xff, 64);

// 最大値を設定します
$chart->SetMax(100);

$chart->Draw($image, $data);

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
