<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Console;

use eTraxis\Dictionary\AccountProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to export dictionaries to frontend as JavaScript constants.
 */
class JsConstCommand extends Command
{
    protected static $defaultName = 'etraxis:js-const';

    protected $dictionaries = [
        AccountProvider::class => 'PROVIDER',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Exports dictionaries to frontend as JavaScript constants');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $javascript = [
            '// This file is autogenerated using ' . self::$defaultName,
        ];

        foreach ($this->dictionaries as $dictionary => $prefix) {

            $reflection = new \ReflectionClass($dictionary);
            $constants  = $reflection->getConstants();

            foreach ($constants as $constant => $value) {

                if ($constant !== 'FALLBACK') {

                    $javascript[] = sprintf(
                        'export const %s_%s = %s;',
                        $prefix,
                        $constant,
                        $value === null ? 'null' : "'{$value}'"
                    );
                }
            }
        }

        file_put_contents('assets/js/const.js', implode("\n", $javascript));

        $io = new SymfonyStyle($input, $output);

        $io->success('Successfully exported.');
    }
}
