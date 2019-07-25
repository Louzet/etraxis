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
class IssueEx extends Issue
{
    /**
     * @API\Property(type="object", description="Actions availability.", properties={
     *     @API\Property(property="issue.view",           type="boolean"),
     *     @API\Property(property="issue.update",         type="boolean"),
     *     @API\Property(property="issue.delete",         type="boolean"),
     *     @API\Property(property="state.change",         type="array", @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\StateInfo::class)
     *     )),
     *     @API\Property(property="issue.reassign",       type="array", @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\UserInfo::class)
     *     )),
     *     @API\Property(property="issue.suspend",        type="boolean"),
     *     @API\Property(property="issue.resume",         type="boolean"),
     *     @API\Property(property="comment.public.add",   type="boolean"),
     *     @API\Property(property="comment.private.add",  type="boolean"),
     *     @API\Property(property="comment.private.read", type="boolean"),
     *     @API\Property(property="file.attach",          type="boolean"),
     *     @API\Property(property="file.delete",          type="boolean"),
     *     @API\Property(property="dependency.add",       type="boolean"),
     *     @API\Property(property="dependency.remove",    type="boolean")
     * })
     */
    public $options;
}
