<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

class Verifycode
{

    //图片宽度 像素
    private $imageWidth;

    //图片高度 像素
    private $imageHeight;

    //验证码
    private $code;

    //文件保存路径
    private $file;

    function __construct($imageWidth, $imageHeight, $code)
    {
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        $this->code = $code;
        // $this->file = $file;
    }

    function CreateVerifacationImage()
    {

        //设置验证码大小的函数
        $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

        //验证码颜色RGB为(255,255,255)#ffffff
        $bgcolor = imagecolorallocate($image, 255, 255, 255);

        //区域填充
        imagefill($image, 0, 0, $bgcolor);

        $codeArray = str_split($this->code);

        for ($i = 0; $i < count($codeArray); $i++) {

            //设置字体大小
            $fontsize = 7;

            //数字越大，颜色越浅，这里是深颜色0-120
            //0-255可选
            $fontcolor = imagecolorallocate($image, rand(40, 150), rand(40, 150), rand(40, 150));

            //验证码内容
            $fontcontent = $codeArray[$i];

            //随机坐标
            $x = ($i * 150 / 4) + rand(5, 10);
            $y = rand(5, 10);

            imagestring($image, $fontsize, (int)$x, (int)$y, $fontcontent, $fontcolor);
        }

        //设置干扰元素，设置雪花点
        for ($i = 0; $i < 300; $i++) {

            //设置颜色，20-200颜色比数字浅，不干扰阅读
            $inputcolor = imagecolorallocate($image, rand(50, 200), rand(20, 200), rand(50, 200));

            //画一个单一像素的元素            
            imagesetpixel($image, rand(1, 149), rand(1, 39), $inputcolor);
        }

        //增加干扰元素，设置横线(先设置线的颜色，在设置横线)
        for ($i = 0; $i < 4; $i++) {

            //设置线的颜色
            $linecolor = imagecolorallocate($image, rand(20, 220), rand(20, 220), rand(20, 220));

            imageline($image, rand(1, 149), rand(1, 39), rand(1, 299), rand(1, 149), $linecolor);
        }

        //建立png函数 输出到文件
        ob_start();

        imagepng($image);

        $imageString = base64_encode(ob_get_contents());

        //结束图形函数，消除$image
        imagedestroy($image);

        ob_end_clean();

        return 'data:image/png;base64,' . $imageString;
    }
}
