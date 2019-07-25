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
class ProjectEx extends Project
{
    /**
     * @API\Property(type="object", description="Actions availability.", properties={
     *     @API\Property(property="project.update",  type="boolean"),
     *     @API\Property(property="project.delete",  type="boolean"),
     *     @API\Property(property="project.suspend", type="boolean"),
     *     @API\Property(property="project.resume",  type="boolean"),
     *     @API\Property(property="template.create", type="boolean")
     * })
     */
    public $options;
}
