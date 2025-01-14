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

namespace eTraxis\Twig;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

/**
 * @coversDefaultClass \eTraxis\Twig\LocaleExtension
 */
class LocaleExtensionTest extends TestCase
{
    /**
     * @covers ::getFilters
     */
    public function testFilters()
    {
        $expected = [
            'direction',
            'language',
        ];

        $extension = new LocaleExtension();

        $filters = array_map(function (TwigFilter $filter) {
            return $filter->getName();
        }, $extension->getFilters());

        self::assertSame($expected, $filters);
    }

    /**
     * @covers ::filterDirection
     */
    public function testFilterDirection()
    {
        $extension = new LocaleExtension();

        self::assertSame(LocaleExtension::LEFT_TO_RIGHT, $extension->filterDirection('en'));
        self::assertSame(LocaleExtension::RIGHT_TO_LEFT, $extension->filterDirection('ar'));
        self::assertSame(LocaleExtension::RIGHT_TO_LEFT, $extension->filterDirection('fa'));
        self::assertSame(LocaleExtension::RIGHT_TO_LEFT, $extension->filterDirection('he'));
    }

    /**
     * @covers ::filterLanguage
     */
    public function testFilterLanguage()
    {
        $extension = new LocaleExtension();

        self::assertSame('Русский', $extension->filterLanguage('ru'));
    }
}
