<?php

$finder = Symfony\Component\Finder\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

    return (new PhpCsFixer\Config())->setRules([
            '@PSR12' => true,
            'no_unused_imports' => true,
            'single_line_after_imports' => true,
            'no_extra_blank_lines' => true,
        ])
        ->setCacheFile(__DIR__.'/.php-cs-fixer.cache')
        ->setLineEnding("\n")
        ->setFinder($finder);
