<?php

$config = new PhpCsFixer\Config();

return $config
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . DIRECTORY_SEPARATOR . '.cache' . DIRECTORY_SEPARATOR . 'php-cs-fixer.cache')
    ->setHideProgress(false)
    ->setRules([
        '@PSR12'                              => true,
        'array_indentation'                   => true,
        'array_syntax'                        => ['syntax' => 'short'],
        'binary_operator_spaces'              => ['operators' => ['=>' => 'align_single_space']],
        'blank_line_before_statement'         => true,
        'class_attributes_separation'         => true,
        'concat_space'                        => ['spacing' => 'one'],
        'no_blank_lines_after_phpdoc'         => true,
        'no_empty_comment'                    => true,
        'no_empty_phpdoc'                     => true,
        'no_empty_statement'                  => true,
        'no_extra_blank_lines'                => true,
        'no_short_bool_cast'                  => true,
        'object_operator_without_whitespace'  => true,
        'phpdoc_indent'                       => true,
        'phpdoc_no_access'                    => true,
        'phpdoc_no_empty_return'              => true,
        'phpdoc_no_useless_inheritdoc'        => true,
        'phpdoc_order'                        => true,
        'phpdoc_scalar'                       => true,
        'phpdoc_separation'                   => true,
        'phpdoc_trim'                         => true,
        'phpdoc_types'                        => true,
        'phpdoc_var_annotation_correct_order' => true,
        'single_quote'                        => ['strings_containing_single_quote_chars' => true],
   ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->files()
        ->name('*.php')
        ->ignoreVCS(true)
        ->ignoreDotFiles(true)
        ->in(__DIR__ . '/lib/Cake')
    );
