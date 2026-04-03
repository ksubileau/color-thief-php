<?php

/*
 * This file is part of the Color Thief PHP project.
 *
 * (c) Kevin Subileau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ColorThief\Image\Adapter;

use ColorThief\Exception\NotReadableException;
use ColorThief\Exception\NotSupportedException;

/**
 * Base adapter implementation to handle image manipulation.
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /** The image instance of current image adapter. */
    protected ?object $resource = null;

    /**
     * Creates new instance of the image adapter.
     */
    public function __construct()
    {
        if (!$this->isAvailable()) {
            throw new NotSupportedException('Image adapter is not available with this PHP installation. Required extension may be missing.');
        }
    }

    public function load(mixed $resource): AdapterInterface
    {
        $this->resource = $resource;

        return $this;
    }

    public function loadFromUrl(string $url): AdapterInterface
    {
        $context = stream_context_create([
                'http' => [
                'method' => 'GET',
                // force use HTTP 1.1 for service mesh environment with envoy
                'protocol_version' => 1.1,
                'header' => [
                    'Accept-language: en',
                    'User-Agent: ColorThief Library',
                ],
            ],
        ]);

        $data = file_get_contents($url, false, $context);

        if (false === $data) {
            throw new NotReadableException("Unable to load image from url ({$url}).");
        }

        return $this->loadFromBinary($data);
    }

    public function destroy(): void
    {
        $this->resource = null;
    }

    public function getResource(): object|null
    {
        return $this->resource;
    }
}
