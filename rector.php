<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/src',
		__DIR__ . '/tests',
	])
	->withSkip([
		__DIR__ . '/vendor',
	])
	->withSets([
		// Apply PHP 8.2 level set (current project requirement)
		LevelSetList::UP_TO_PHP_82,

		// Additional useful sets
		SetList::CODE_QUALITY,
		SetList::DEAD_CODE,
		SetList::TYPE_DECLARATION,
		SetList::PRIVATIZATION,
	])
	->withRules([
		ExplicitNullableParamTypeRector::class,
	])
	->withPhpSets(php82: true);
