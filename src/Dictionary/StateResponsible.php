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
 * State responsibility values.
 */
class StateResponsible extends StaticDictionary
{
    public const KEEP   = 'keep';
    public const ASSIGN = 'assign';
    public const REMOVE = 'remove';

    protected static $dictionary = [
        self::KEEP   => 'state.responsible.keep',
        self::ASSIGN => 'state.responsible.assign',
        self::REMOVE => 'state.responsible.remove',
    ];
}
