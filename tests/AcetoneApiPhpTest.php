<?php

declare(strict_types=1);

namespace avadim\Acetone;

use avadim\Acetone\AcetoneApi;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
class AcetoneApiPhpTest extends TestCase
{
    protected string $imageFile = __DIR__ . '/../demo/pics/fg-1200x1200.jpg';
    protected string $imageFileBg = __DIR__ . '/../demo/pics/bg-1332x850.jpg';


    protected function _rgba($file, $x, $y)
    {
        $image = imagecreatefromstring(file_get_contents($file));
        $color = imagecolorat($image, $x, $y);

        return imagecolorsforindex($image, $color);
    }

    public function test()
    {
        $apiKey = include __DIR__ . '/.key.php';
        $acetone = new AcetoneApi($apiKey);

        // ===
        $out = __DIR__ . '/test.jpg';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([1200, 1200, 'image/jpeg'], [$size[0], $size[1], $size['mime']]);
        unlink($out);

        // ===
        $out = __DIR__ . '/test.webp';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([1200, 1200, 'image/webp'], [$size[0], $size[1], $size['mime']]);
        unlink($out);

        // ===
        $out = __DIR__ . '/test.png';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([1200, 1200, 'image/png'], [$size[0], $size[1], $size['mime']]);
        $rgba = $this->_rgba($out, 1, 1);
        $this->assertEquals(127, $rgba['alpha']);
        unlink($out);

        // ===
        $out = __DIR__ . '/test-color.png';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->bgColor('#f00')
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([1200, 1200, 'image/png'], [$size[0], $size[1], $size['mime']]);
        $rgba = $this->_rgba($out, 1, 1);
        $this->assertEquals(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], $rgba);
        unlink($out);

        // ===
        $out = __DIR__ . '/test-grad.png';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->size(1200, 800, AcetoneApi::IMG_FIT_CONTAIN)
            ->bgGradient([[255,0,0], '0000ff'])
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([1200, 800, 'image/png'], [$size[0], $size[1], $size['mime']]);
        $rgba = $this->_rgba($out, 1, 1);
        $this->assertEquals(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], $rgba);
        $rgba = $this->_rgba($out, 1, 799);
        $this->assertEquals(['red' => 0, 'green' => 0, 'blue' => 255, 'alpha' => 0], $rgba);
        unlink($out);

        // ===
        $out = __DIR__ . '/test-bg.png';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->size(800, 600, AcetoneApi::IMG_FIT_CONTAIN, AcetoneApi::IMG_FIT_COVER)
            ->bgImageFile($this->imageFileBg)
            ->save($out);

        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([800, 600, 'image/png'], [$size[0], $size[1], $size['mime']]);
        $rgba = $this->_rgba($out, 10, 10);
        $this->assertEquals(['red' => 127, 'green' => 127, 'blue' => 16, 'alpha' => 0], $rgba);
        unlink($out);
    }
}