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

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as API;

/**
 * Descriptive class for API annotations.
 */
class Template
{
    /**
     * @API\Property(type="integer", example=123, description="Template ID.")
     */
    public $id;

    /**
     * @API\Property(type="object", ref=@Model(type=eTraxis\Swagger\Project::class), description="Template project.")
     */
    public $project;

    /**
     * @API\Property(type="string", example="Bugfix", description="Template name.")
     */
    public $name;

    /**
     * @API\Property(type="string", example="bug", description="Template prefix (used as a prefix in ID of the issues, created using this template).")
     */
    public $prefix;

    /**
     * @API\Property(type="string", example="Error reports", description="Optional description.")
     */
    public $description;

    /**
     * @API\Property(type="integer", example=5, description="When an issue remains opened for more than this amount of days it's displayed in red in the list of issues.")
     */
    public $critical;

    /**
     * @API\Property(type="integer", example=10, description="When an issue is closed a user cannot change its state anymore, but one still can edit its fields, add comments and attach files. If frozen time is specified it will be allowed to edit the issue this amount of days after its closure. After that the issue becomes read-only. If this attribute is not specified, an issue will never become read-only.")
     */
    public $frozen;

    /**
     * @API\Property(type="boolean", example=false, description="Whether the template is locked for edition.")
     */
    public $locked;
}
