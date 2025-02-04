<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\CaseConverter;

use function Kahlan\expect;

describe('CaseConverter', function () {
    it('should convert string to dot anotation', function () {
        $data = [
            'camelCase'           => 'camel.case',
            'camelCaseMultiple'   => 'camel.case.multiple',
            'PascalCase'          => 'pascal.case',
            'PascalCaseMultiple'  => 'pascal.case.multiple',
            'kebab-case'          => 'kebab.case',
            'kebab-case-multiple' => 'kebab.case.multiple',
            'snake_case'          => 'snake.case',
            'snake_case_multiple' => 'snake.case.multiple',
            'dot.case'            => 'dot.case',
            'dot.case.multiple'   => 'dot.case.multiple',
            'world case'          => 'world.case',
            'world case multiple' => 'world.case.multiple',
        ];

        foreach ($data as $actual => $expected) {
            expect(CaseConverter::toDot($actual))->toBe($expected);
        }
    });

    it('should convert string to snake case', function () {
        $data = [
            'camelCase'           => 'camel_case',
            'camelCaseMultiple'   => 'camel_case_multiple',
            'PascalCase'          => 'pascal_case',
            'PascalCaseMultiple'  => 'pascal_case_multiple',
            'kebab-case'          => 'kebab_case',
            'kebab-case-multiple' => 'kebab_case_multiple',
            'snake_case'          => 'snake_case',
            'snake_case_multiple' => 'snake_case_multiple',
            'dot.case'            => 'dot_case',
            'dot.case.multiple'   => 'dot_case_multiple',
            'world case'          => 'world_case',
            'world case multiple' => 'world_case_multiple',
        ];

        foreach ($data as $actual => $expected) {
            expect(CaseConverter::toSnake($actual))->toBe($expected);
        }
    });
});
