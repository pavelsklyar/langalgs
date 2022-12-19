<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
    ]);

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        'array_indentation' => true,
        'array_push' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],

        'blank_line_after_namespace' => true,
        'single_blank_line_before_namespace' => true,

        'blank_line_after_opening_tag' => true,

        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
        ],

        'fopen_flags' => [
            'b_mode' => true,
        ],

        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'increment_style' => [
            'style' => 'post',
        ],

        'cast_spaces' => [
            'space' => 'single',
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],

        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'method_chaining_indentation' => false,
        'method_argument_space' => false,
        'no_space_around_double_colon' => true,

        'class_definition' => [
            'multi_line_extends_each_single_line' => true,
        ],

        'single_line_throw' => false,
        'static_lambda' => true,
        'return_assignment' => false,
        'no_unset_on_property' => false,

        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => true,
        ],
        'phpdoc_to_comment' => [
            'ignored_tags' => [
                'psalm-suppress',
                'var',
            ]
        ],
        'phpdoc_no_alias_tag' => [
            'replacements' => [],
        ],
        'phpdoc_types_order' => false,
        'phpdoc_add_missing_param_annotation' => false,
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'phpdoc_separation' => true,

        'php_unit_strict' => false,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'this',
        ],
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => [],

        'global_namespace_import' => [
            'import_classes' => true,
        ],
    ])
    ->setFinder($finder);
