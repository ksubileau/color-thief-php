<?php
namespace ColorThief\Test;

use ColorThief\ColorThief;

class ColorThiefTest extends \PHPUnit_Framework_TestCase
{
    public function dominantImageSet()
    {
        return array(
                array(
                        "/images/rails_600x406.gif",
                        array(88, 70, 80)
                    ),
                array(
                        "/images/field_1024x683.jpg",
                        array(107, 172, 222)
                    ),
                array(
                        "/images/vegetables_1500x995.png",
                        array(211, 198, 172)
                    ),
            );
    }

    public function paletteImageSet()
    {
        return array(
                array(
                    "/images/rails_600x406.gif",
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
                    "/images/field_1024x683.jpg",
                    array(
                        array(69, 52, 37),
                        array(91, 166, 223),
                        array(146, 188, 219),
                        array(186, 212, 228),
                        array(42, 140, 216),
                        array(132, 145, 147),
                        array(60, 92, 120),
                        array(168, 140, 88),
                        array(94, 116, 125)
                    )
                ),
                array(
                    "/images/vegetables_1500x995.png",
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

    /**
     * @dataProvider dominantImageSet
     */
    public function testDominantColor($image, $expectedColor)
    {
        $dominantColor = ColorThief::getColor(__DIR__.$image);

        $this->assertInternalType('array', $dominantColor);
        $this->assertCount(3, $dominantColor);
        $this->assertEquals($expectedColor, $dominantColor);
    }

    /**
     * @dataProvider paletteImageSet
     */
    public function testPalette($image, $expectedPalette)
    {
        //$numColors = count($expectedPalette);
        $numColors = 10;
        $palette = ColorThief::getPalette(__DIR__.$image, $numColors, 30);

        $this->assertInternalType('array', $palette);
        //$this->assertCount($numColors, $palette);
        $this->assertEquals($expectedPalette, $palette);
    }
}
