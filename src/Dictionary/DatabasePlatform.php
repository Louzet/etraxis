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

namespace eTraxis\Dictionary;

use Dictionary\StaticDictionary;

/**
 * Supported database platforms.
 */
class DatabasePlatform extends StaticDictionary
{
    public const MYSQL      = 'mysql';
    public const POSTGRESQL = 'postgresql';

    protected static $dictionary = [
        self::MYSQL      => 'MySQL',
        self::POSTGRESQL => 'PostgreSQL',
    ];
}
