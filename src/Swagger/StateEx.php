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

namespace eTraxis\Swagger;

use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class StateEx extends State
{
    /**
     * @API\Property(type="object", description="Actions availability.", properties={
     *     @API\Property(property="state.update",             type="boolean"),
     *     @API\Property(property="state.delete",             type="boolean"),
     *     @API\Property(property="state.set_initial",        type="boolean"),
     *     @API\Property(property="state.transitions",        type="boolean"),
     *     @API\Property(property="state.responsible_groups", type="boolean"),
     *     @API\Property(property="field.create",             type="boolean")
     * })
     */
    public $options;
}
