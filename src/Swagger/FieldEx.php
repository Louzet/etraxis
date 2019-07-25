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
class FieldEx extends Field
{
    /**
     * @API\Property(type="object", description="Actions availability.", properties={
     *     @API\Property(property="field.update",      type="boolean"),
     *     @API\Property(property="field.remove",      type="boolean"),
     *     @API\Property(property="field.delete",      type="boolean"),
     *     @API\Property(property="field.permissions", type="boolean"),
     *     @API\Property(property="listitem.create",   type="boolean")
     * })
     */
    public $options;
}
