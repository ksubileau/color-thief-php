<?php

declare(strict_types=1);

$header = <<<'HEADER'
This file is part of the Color Thief PHP project.

(c) Kevin Subileau

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/lib')
    ->in(__DIR__ . '/tests');

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        '@PHPUnit75Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'header_comment' => [
            'header' => $header,
            'location' => 'after_open',
        ],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'phpdoc_order' => true,
        'trailing_comma_in_multiline' => true,
        'visibility_required' => [
            'elements' => ['const', 'method', 'property']
        ]
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);