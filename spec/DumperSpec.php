<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Dumper;

use function Kahlan\expect;

class DumperSpec
{
    use Dumper;
}

describe('Dumper', function () {
    it('should set option using option class', function () {
        $fake = new DumperSpec('fake', pdo());

        expect($fake->getOption()->add_drop_database)->toBeFalsy();

        $option = new Dimtrovich\DbDumper\Option(['add_drop_database' => true]);
        $fake->setOption($option);

        expect($fake->getOption()->add_drop_database)->toBeTruthy();
    });

    it('should set option using array of options', function () {
        $fake = new DumperSpec('fake', pdo());

        expect($fake->getOption()->add_drop_database)->toBeFalsy();

        $fake->setOption(['add_drop_database' => true]);

        expect($fake->getOption()->add_drop_database)->toBeTruthy();
    });
});
