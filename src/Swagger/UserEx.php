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
class UserEx extends User
{
    /**
     * @API\Property(type="object", description="Actions availability.", properties={
     *     @API\Property(property="user.update",   type="boolean"),
     *     @API\Property(property="user.delete",   type="boolean"),
     *     @API\Property(property="user.disable",  type="boolean"),
     *     @API\Property(property="user.enable",   type="boolean"),
     *     @API\Property(property="user.unlock",   type="boolean"),
     *     @API\Property(property="user.password", type="boolean")
     * })
     */
    public $options;
}
