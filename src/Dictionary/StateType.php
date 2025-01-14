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
 * State types.
 */
class StateType extends StaticDictionary
{
    public const INITIAL      = 'initial';
    public const INTERMEDIATE = 'intermediate';
    public const FINAL        = 'final';

    protected static $dictionary = [
        self::INITIAL      => 'state.type.initial',
        self::INTERMEDIATE => 'state.type.intermediate',
        self::FINAL        => 'state.type.final',
    ];
}
