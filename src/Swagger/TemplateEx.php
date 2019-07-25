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
class TemplateEx extends Template
{
    /**
     * @API\Property(type="object", description="Actions availability.", properties={
     *     @API\Property(property="template.update",      type="boolean"),
     *     @API\Property(property="template.delete",      type="boolean"),
     *     @API\Property(property="template.lock",        type="boolean"),
     *     @API\Property(property="template.unlock",      type="boolean"),
     *     @API\Property(property="template.permissions", type="boolean"),
     *     @API\Property(property="state.create",         type="boolean"),
     *     @API\Property(property="issue.create",         type="boolean")
     * })
     */
    public $options;
}
