<?php

namespace ColorThief;

/**
 * Color map.
 */
class CMap
{
    /**
     * @var PQueue
     * @phpstan-var PQueue<array{vbox: VBox, color: ColorRGB}>
     */
    private $vboxes;

    public function __construct()
    {
        $this->vboxes = new PQueue(function ($a, $b) {
            return ColorThief::naturalOrder(
                $a['vbox']->count() * $a['vbox']->volume(),
                $b['vbox']->count() * $b['vbox']->volume()
            );
        });
    }

    public function push(VBox $vbox): void
    {
        $this->vboxes->push([
            'vbox' => $vbox,
            'color' => $vbox->avg(),
        ]);
    }

    /**
     * @phpstan-return ColorRGB[]
     */
    public function palette(): array
    {
        return $this->vboxes->map(function ($vb) {
            return $vb['color'];
        });
    }

    public function size(): int
    {
        return $this->vboxes->size();
    }

    /**
     * @phpstan-param ColorRGB $color
     * @phpstan-return ColorRGB|null $color
     */
    public function map(array $color): ?array
    {
        $vboxes_size = $this->vboxes->size();
        for ($i = 0; $i < $vboxes_size; $i++) {
            $vbox = $this->vboxes->peek($i);
            if ($vbox['vbox']->contains($color)) {
                return $vbox['color'];
            }
        }

        return $this->nearest($color);
    }

    /**
     * @phpstan-param ColorRGB $color
     * @phpstan-return ColorRGB|null $color
     */
    public function nearest(array $color): ?array
    {
        $pColor = null;
        $vboxes_size = $this->vboxes->size();
        for ($i = 0; $i < $vboxes_size; $i++) {
            $vbox = $this->vboxes->peek($i);
            $d2 = sqrt(
                ($color[0] - $vbox['color'][0]) ** 2 +
                ($color[1] - $vbox['color'][1]) ** 2 +
                ($color[2] - $vbox['color'][2]) ** 2
            );

            if (!isset($d1) || $d2 < $d1) {
                $d1 = $d2;
                $pColor = $vbox['color'];
            }
        }

        return $pColor;
    }

    public function forcebw(): void
    {
        // XXX: won't work yet
        /*
        vboxes = this.vboxes;
        vboxes.sort(function (a,b) { return pv.naturalOrder(pv.sum(a.color), pv.sum(b.color) )});

        // force darkest color to black if everything < 5
        var lowest = vboxes[0].color;
        if (lowest[0] < 5 && lowest[1] < 5 && lowest[2] < 5)
            vboxes[0].color = [0,0,0];

        // force lightest color to white if everything > 251
        var idx = vboxes.length-1,
            highest = vboxes[idx].color;
        if (highest[0] > 251 && highest[1] > 251 && highest[2] > 251)
            vboxes[idx].color = [255,255,255];
        */
    }
}
