<?php

declare(strict_types=1);

namespace AcetoneSoft\Acetone;

use AcetoneSoft\Acetone\AcetoneApi;
use PHPUnit\Framework\TestCase;

class AcetoneApiPhpTest extends TestCase
{
    protected string $imageFile = __DIR__ . '/../demo/pics/fg-1200x1200.jpg';
    protected string $imageFileBg = __DIR__ . '/../demo/pics/bg-1332x850.jpg';
    protected string $apiKey = '';

    protected function _rgba($file, $x, $y)
    {
        $image = imagecreatefromstring(file_get_contents($file));
        $color = imagecolorat($image, $x, $y);

        return imagecolorsforindex($image, $color);
    }


    protected function getApiKey(): string
    {
        if (!$this->apiKey) {
            $this->apiKey = getenv('ACETONE_API_KEY') ?: (string)($_ENV['ACETONE_API_KEY'] ?? '');
            if (!$this->apiKey || $this->apiKey === '00000000-0000-0000-0000-000000000000') {
                $this->markTestSkipped('Set ACETONE_API_KEY (e.g. in .env) to run the API tests');
            }
        }

        return $this->apiKey;
    }


    public function test()
    {
        $apiKey = $this->getApiKey();
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

    public function testColors()
    {
        $apiKey = $this->getApiKey();
        $acetone = new AcetoneApi($apiKey);

        // ===
        $out = __DIR__ . '/test-color.png';
        if (is_file($out)) {
            unlink($out);
        }
        $res = $acetone->fromFile($this->imageFile)
            ->bgColor([255, 0, 0])
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $size = getimagesize($out);
        $this->assertEquals([1200, 1200, 'image/png'], [$size[0], $size[1], $size['mime']]);
        $rgba = $this->_rgba($out, 1, 1);
        $this->assertEquals(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], $rgba);
        unlink($out);

        $res = $acetone->fromFile($this->imageFile)
            ->bgColor('[255, 0, 0]')
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $rgba = $this->_rgba($out, 1, 1);
        $this->assertEquals(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], $rgba);
        unlink($out);

        $res = $acetone->fromFile($this->imageFile)
            ->bgColor('#ff0000')
            ->save($out);
        $this->assertTrue($res && is_file($out));
        $rgba = $this->_rgba($out, 1, 1);
        $this->assertEquals(['red' => 255, 'green' => 0, 'blue' => 0, 'alpha' => 0], $rgba);
        unlink($out);

        $res = $acetone->fromFile($this->imageFile)
            ->bgColor('#f00')
            ->save($out);
        $this->assertTrue($res && is_file($out));
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
    }

    public function testShadowAndQuality()
    {
        $acetone = new AcetoneApi($this->getApiKey());

        $image = $acetone->fromFile($this->imageFile)
            ->shadow(50, 15, 15, '#999999')
            ->quality(90)
            ->exact()
            ->get('png');

        $size = getimagesizefromstring($image);
        $this->assertNotFalse($size);
        $this->assertEquals('image/png', $size['mime']);
    }

    public function testObjectRemove()
    {
        $acetone = new AcetoneApi($this->getApiKey());

        // Build a simple mask: black background with a white rectangle in the centre
        [$w, $h] = getimagesize($this->imageFile);
        $mask = imagecreatetruecolor($w, $h);
        imagefill($mask, 0, 0, imagecolorallocate($mask, 0, 0, 0));
        imagefilledrectangle(
            $mask,
            (int)($w * 0.4), (int)($h * 0.4),
            (int)($w * 0.6), (int)($h * 0.6),
            imagecolorallocate($mask, 255, 255, 255)
        );
        ob_start();
        imagepng($mask);
        $maskBin = ob_get_clean();

        $image = $acetone->objectRemove(file_get_contents($this->imageFile), $maskBin);

        $size = getimagesizefromstring($image);
        $this->assertNotFalse($size);
        $this->assertGreaterThan(0, $size[0]);
        $this->assertGreaterThan(0, $size[1]);
    }

    public function testEnhance()
    {
        $acetone = new AcetoneApi($this->getApiKey());

        $image = $acetone->fromFile($this->imageFile)
            ->enhanceMode('solo')
            ->getEnhanced('png');

        $size = getimagesizefromstring($image);
        $this->assertNotFalse($size);
        $this->assertEquals('image/png', $size['mime']);
    }
}