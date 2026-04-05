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
		// ++ has no effect on SplFixedArray elements, and CombinedAssignRector
		// converts $a = $a + 1 to $a += 1, which cs-fixer then converts to ++$a
		CombinedAssignRector::class,
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
