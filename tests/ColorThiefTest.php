<?php
namespace ColorThief\Test;

use ColorThief\ColorThief;

class ColorThiefTest extends \PHPUnit_Framework_TestCase
{
    public function provideImageDominantColor()
    {
        return array(
                array(
                        "/images/rails_600x406.gif",
                        null,
                        array(88, 70, 80)
                    ),
                array(
                        "/images/field_1024x683.jpg",
                        null,
                        array(107, 172, 222)
                    ),
                array(  // Area targeting
                        "/images/vegetables_1500x995.png",
                        array('x' => 670, 'y' => 215, 'w' => 230, 'h' => 120),
                        array(63, 112, 24)
                    ),
                array(  // Area targeting with default values for y and width.
                        "/images/vegetables_1500x995.png",
                        array('x' => 1300, 'h' => 500),
                        array(54, 60, 33)
                    ),
            );
    }

    public function provideImageColorPalette()
    {
        return array(
                array(
                    "/images/rails_600x406.gif",
                    null,
                    array(
                        array(87, 68, 79),
                        array(210, 170, 127),
                        array(158, 113, 84),
                        array(157, 190, 175),
                        array(107, 119, 129),
                        array(52, 136, 211),
                        array(29, 68, 84),
                        array(120, 124, 101),
                        array(212, 76, 60)
                    )
                ),
                array(
                    "/images/vegetables_1500x995.png",
                    null,
                    array(
                        array(45, 58, 23),
                        array(227, 217, 199),
                        array(96, 59, 49),
                        array(117, 122, 46),
                        array(107, 129, 102),
                        array(176, 153, 102),
                        array(191, 180, 144),
                        array(159, 132, 146),
                        array(60, 148, 44)
                    )
                ),
            );
    }

    public function provide8bitsColorIndex()
    {
        return array(
          array(  0,   0,   0,        0),
          array(120, 120, 120,  7895160),
          array(255, 255, 255, 16777215)
        );
    }

    public function provide5bitsColorIndex()
    {
        return array(
          array(  0,   0,   0,      0),
          array(120, 120, 120, 126840),
          array(255, 255, 255, 269535)
        );
    }

    public function provideNaturalOrderComparison()
    {
        return array(
          array(0, 5, -1),
          array(10, -3, 1),
          array(3, 3, 0)
        );
    }

    protected function getBlankImage($width = 20, $height = 30)
    {
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);
        return $img;
    }

    /**
     * @dataProvider provideImageDominantColor
     */
    public function testDominantColor($image, $area, $expectedColor)
    {
        $dominantColor = ColorThief::getColor(__DIR__.$image, 10, $area);

        $this->assertSame($expectedColor, $dominantColor);
    }

    /**
     * @see Issue #13
     */
    public function testRemoteImage()
    {
        $dominantColor = ColorThief::getColor(
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/rails_600x406.gif"
        );
        $this->assertSame(array(88, 70, 80), $dominantColor);
    }

    /**
     * @dataProvider provideImageColorPalette
     */
    public function testPalette($image, $area, $expectedPalette)
    {
        //$numColors = count($expectedPalette);
        $numColors = 10;
        $palette = ColorThief::getPalette(__DIR__.$image, $numColors, 30, $area);

        //$this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The number of palette colors
     */
    public function testGetPaletteWithTooFewColors()
    {
        ColorThief::getPalette("foo.jpg", 1);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The number of palette colors
     */
    public function testGetPaletteWithTooManyColors()
    {
        ColorThief::getPalette("foo.jpg", 120000);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage quality argument
     */
    public function testGetPaletteWithInvalidQuality()
    {
        ColorThief::getPalette("foo.jpg", 5, 0);
    }

    /**
     * @see Issue #11
     * @expectedException RuntimeException
     * @expectedExceptionMessage blank or transparent image
     * @expectedExceptionCode 1
     */
    public function testGetPaletteWithBlankImage()
    {
        ColorThief::getPalette($this->getBlankImage());
    }

    /**
     * @dataProvider provide8bitsColorIndex
     */
    public function testGetColorIndex8bits($r, $g, $b, $index)
    {
        $this->assertSame(
            $index,
            ColorThief::getColorIndex($r, $g, $b, 8)
        );
    }

    /**
     * @dataProvider provide5bitsColorIndex
     */
    public function testGetColorIndex5bits($r, $g, $b, $index)
    {
        $this->assertSame(
            $index,
            ColorThief::getColorIndex($r, $g, $b)
        );
    }

    /**
     * @dataProvider provide8bitsColorIndex
     */
    public function testGetColorsFromIndex8bits($r, $g, $b, $index)
    {
        $this->assertSame(
            array($r, $g, $b),
            ColorThief::getColorsFromIndex($index, 0)
        );
    }

    /**
     * @dataProvider provideNaturalOrderComparison
     */
    public function testNaturalOrder($left, $right, $expected)
    {
        $this->assertSame(
            $expected,
            ColorThief::naturalOrder($left, $right)
        );
    }

    public function testGetHisto()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'getHisto');
        $method->setAccessible(true);

        // [[229, 210, 51], [133, 24, 135], [216, 235, 108], [132, 25, 134], [223, 46, 29],
        // [135, 28, 132], [233, 133, 213], [225, 212, 48]]
        $pixels = array(15061555, 8722567, 14216044, 8657286, 14626333, 8854660, 15304149, 14799920);

        $expectedHisto = array (
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1
        );

        $this->assertSame($expectedHisto, $method->invoke(null, $pixels));
    }

    public function testVboxFromPixels()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'vboxFromHistogram');
        $method->setAccessible(true);

        // [[229, 210, 51], [133, 24, 135], [216, 235, 108], [132, 25, 134], [223, 46, 29],
        // [135, 28, 132], [233, 133, 213], [225, 212, 48]]
        //$pixels = array(15061555, 8722567, 14216044, 8657286, 14626333, 8854660, 15304149, 14799920);

        $histo = array (
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1
        );

        $result = $method->invoke(null, $histo);

        $this->assertInstanceOf('\ColorThief\VBox', $result);
        $this->assertSame($histo, $result->histo);
        $this->assertSame(16, $result->r1);
        $this->assertSame(29, $result->r2);
        $this->assertSame(3, $result->g1);
        $this->assertSame(29, $result->g2);
        $this->assertSame(3, $result->b1);
        $this->assertSame(26, $result->b2);
    }

    public function testDoCutLeftLetherThanRight()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'doCut');
        $method->setAccessible(true);

        $histo = array (
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1
        );

        // $left <= $right
        $result = $method->invoke(
            null,
            "g",
            new \ColorThief\VBox(0, 20, 0, 31, 0, 31, null),
            array(38,149,556,1222,1830,2656,3638,4744,6039,7412,9039,10686,12244,13715,15091,16355,17599,18768,19771,
                20925,22257,24094,25782,27585,28796,29794,30258,30290,30298,30301,30301,30301),
            30301,
            array(30263,30152,29745,29079,28471,27645,26663,25557,24262,22889,21262,19615,18057,16586,15210,13946,
                12702,11533,10530,9376,8044,6207,4519,2716,1505,507,43,11,3,0,0,0)
        );

        $this->assertEquals(new \ColorThief\VBox(0, 20, 0, 23, 0, 31, null), $result[0]);
        $this->assertEquals(new \ColorThief\VBox(0, 20, 24, 31, 0, 31, null), $result[1]);
    }

    public function testDoCutLeftGreaterThanRight()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'doCut');
        $method->setAccessible(true);

        $histo = array (
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1
        );

        // $left > $right
        $result = $method->invoke(
            null,
            "g",
            new \ColorThief\VBox(0, 13, 0, 17, 0, 10, null),
            array(38,149,512,1151,1741,2554,3530,4624,5899,7247,8788,10261,11645,12906,13969,14871,15654,16329),
            16329,
            array(16291,16180,15817,15178,14588,13775,12799,11705,10430,9082,7541,6068,4684,3423,2360,1458,675,0)
        );

        $this->assertEquals(new \ColorThief\VBox(0, 13, 0, 4, 0, 10, null), $result[0]);
        $this->assertEquals(new \ColorThief\VBox(0, 13, 5, 17, 0, 10, null), $result[1]);
    }
}
