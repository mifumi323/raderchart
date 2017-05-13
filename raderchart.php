<?php
// RaderChart v1.0.1
//  レーダーチャートを描画するクラス
//  PHP+GD2以降必須
//  https://tgws.plus/dl/raderchart/


class RaderChart
{
    private $radius;
    private $x;
    private $y;
    private $max;

    private $axis_r;
    private $axis_g;
    private $axis_b;
    private $axis_a;
    private $graph_fill_r;
    private $graph_fill_g;
    private $graph_fill_b;
    private $graph_fill_a;
    private $graph_line_r;
    private $graph_line_g;
    private $graph_line_b;
    private $graph_line_a;

    private $text_font;
    private $text_size;
    private $text_margin;
    private $text_r;
    private $text_g;
    private $text_b;
    private $text_a;

    // 半径を指定して構築
    public function __construct($radius)
    {
        $this->radius = $radius;
    }

    // レーダーチャートの中心
    public function SetPosition($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    // 半径内に描かれる最大値(これ以上の値ははみ出します)
    public function SetMax($max)
    {
        $this->max = $max;
    }

    // 補助線を一度に設定
    //  $guide: value, r, g, b, aをキーとする連想配列の配列
    public function SetGuide($guide)
    {
        $this->guide = $guide;
    }

    // 補助線を個別に追加
    public function AddGuide($value, $r, $g, $b, $a=0)
    {
        if (!isset($this->guide)) {
            $this->guide = array();
        }
        $this->guide[] = array(
            'value' => $value,
            'r' => $r, 'g' => $g, 'b' => $b, 'a' => $a,
        );
    }

    // 軸の塗りつぶし色
    public function AddGuideFill($value, $r, $g, $b, $a, $fr, $fg, $fb, $fa)
    {
        if (!isset($this->guide)) {
            $this->guide = array();
        }
        $this->guide[] = array(
            'value' => $value,
             'r' =>  $r,  'g' =>  $g,  'b' =>  $b,  'a' =>  $a,
            'fr' => $fr, 'fg' => $fg, 'fb' => $fb, 'fa' => $fa,
        );
    }

    // 軸の線色
    public function SetGraphAxis($r, $g, $b, $a=0)
    {
        $this->axis_r = $r;
        $this->axis_g = $g;
        $this->axis_b = $b;
        $this->axis_a = $a;
    }

    // グラフ本体の塗りつぶし色
    public function SetGraphFill($r, $g, $b, $a=0)
    {
        $this->graph_fill_r = $r;
        $this->graph_fill_g = $g;
        $this->graph_fill_b = $b;
        $this->graph_fill_a = $a;
    }

    // グラフ本体の線色
    public function SetGraphLine($r, $g, $b, $a=0)
    {
        $this->graph_line_r = $r;
        $this->graph_line_g = $g;
        $this->graph_line_b = $b;
        $this->graph_line_a = $a;
    }

    // TrueTypeフォントを指定する
    //  $font:   フォントファイル
    //  $size:   大きさ
    //  $margin: 周りの余白
    //  $r～$a:  色
    public function SetFont($font, $size, $margin, $r, $g, $b, $a=0)
    {
        $this->text_font = $font;
        $this->text_size = $size;
        $this->text_margin = $margin;
        $this->text_r = $r;
        $this->text_g = $g;
        $this->text_b = $b;
        $this->text_a = $a;
    }

    // 指定の画像上に描画
    //  $image: 画像リソース
    //  $data:  軸名と値の連想配列
    public function Draw($image, $data)
    {
        if (count($data)<3) {
            return;
        }

        $cx = $this->x>0?$this->x:imagesx($image)/2;
        $cy = $this->y>0?$this->y:imagesy($image)/2;

        if ($this->max>0) {
            $max = $this->max;
        } else {
            $max = 1;
            foreach ($data as $value) {
                if ($max<$value) {
                    $max = $value;
                }
            }
        }

        $radius = $this->radius;
        $count = count($data);

        // 色
        $axis_color = $this->AllocateColor($image, $this->axis_r, $this->axis_g, $this->axis_b, $this->axis_a);
        $graph_fill_color = $this->AllocateColor($image, $this->graph_fill_r, $this->graph_fill_g, $this->graph_fill_b, $this->graph_fill_a);
        $graph_line_color = $this->AllocateColor($image, $this->graph_line_r, $this->graph_line_g, $this->graph_line_b, $this->graph_line_a);
        $text_color = $this->AllocateColor($image, $this->text_r, $this->text_g, $this->text_b, $this->text_a);

        // ガイド
        if (is_array($this->guide)) {
            usort($this->guide, create_function('$a,$b', 'return $a[\'value\']<$b[\'value\'];'));
            foreach ($this->guide as $g) {
                $guide_fill_color = $this->AllocateColor($image, $g['fr'], $g['fg'], $g['fb'], $g['fa']);
                $guide_line_color = $this->AllocateColor($image, $g[ 'r'], $g[ 'g'], $g[ 'b'], $g[ 'a']);
                $polygon = array();
                $i = 0;
                foreach ($data as $value) {
                    $angle = $this->ToAngle($i, $count);
                    list($x, $y) = $this->Polar2XY($radius*$g['value']/$max, $angle, $cx, $cy);
                    $polygon[] = $x;
                    $polygon[] = $y;
                    $i++;
                }
                if ($guide_fill_color!==false) {
                    imagefilledpolygon($image, $polygon, $count, $guide_fill_color);
                }
                if ($guide_line_color!==false) {
                    imagepolygon($image, $polygon, $count, $guide_line_color);
                }
            }
        }

        // 軸
        if ($axis_color!==false) {
            $i = 0;
            foreach ($data as $value) {
                $angle = $this->ToAngle($i, $count);
                list($x, $y) = $this->Polar2XY($radius, $angle, $cx, $cy);
                imageline($image, $cx, $cy, $x, $y, $axis_color);
                $i++;
            }
        }

        // グラフ本体
        $polygon = array();
        $i = 0;
        foreach ($data as $value) {
            $angle = $this->ToAngle($i, $count);
            list($x, $y) = $this->Polar2XY($radius*$value/$max, $angle, $cx, $cy);
            $polygon[] = $x;
            $polygon[] = $y;
            $i++;
        }
        if ($graph_fill_color!==false) {
            imagefilledpolygon($image, $polygon, $count, $graph_fill_color);
        }
        if ($graph_line_color!==false) {
            imagepolygon($image, $polygon, $count, $graph_line_color);
        }

        // 文字
        if (strlen($this->text_font)>0 && $text_color!==false && $this->text_size>0) {
            $i = 0;
            foreach ($data as $key => $value) {
                $angle = $this->ToAngle($i, $count);
                $bbox = imagettfbbox($this->text_size, 0, $this->text_font, $key);
                $brad = hypot(($bbox[0]-$bbox[4])*sin($angle), ($bbox[1]-$bbox[5])*cos($angle))/2;
                $bcnt_x = ($bbox[0]+$bbox[4])/2;
                $bcnt_y = ($bbox[1]+$bbox[5])/2;
                list($x, $y) = $this->Polar2XY($radius+$brad+$this->text_margin, $angle, $cx, $cy);
                imagettftext($image, $this->text_size, 0, $x-$bcnt_x, $y-$bcnt_y, $text_color, $this->text_font, $key);
                $i++;
            }
        }
    }

    private function ToAngle($i, $count)
    {
        return 2.0*M_PI*$i/$count;
    }

    private function Polar2XY($r, $t, $ox=0, $oy=0)
    {
        return array(
            (int)((+sin($t))*$r)+$ox,
            (int)((-cos($t))*$r)+$oy,
        );
    }

    private function AllocateColor($image, $r, $g, $b, $a)
    {
        if (!isset($image)) {
            return false;
        }
        if (!isset($r)) {
            return false;
        }
        if (!isset($g)) {
            return false;
        }
        if (!isset($b)) {
            return false;
        }
        if (!isset($a)) {
            return false;
        }
        if ($a>=127) {
            return false;
        }
        return imagecolorallocatealpha($image, $r, $g, $b, $a);
    }
}
