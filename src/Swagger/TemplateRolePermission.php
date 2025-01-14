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
class TemplateRolePermission
{
    /**
     * @API\Property(type="string", enum={
     *     "anyone",
     *     "author",
     *     "responsible"
     * }, example="author", description="System role.")
     */
    public $role;

    /**
     * @API\Property(type="string", enum={
     *     "comment.add",
     *     "comment.private",
     *     "dependency.add",
     *     "dependency.remove",
     *     "file.attach",
     *     "file.delete",
     *     "issue.create",
     *     "issue.delete",
     *     "issue.edit",
     *     "issue.reassign",
     *     "issue.reopen",
     *     "issue.resume",
     *     "issue.suspend",
     *     "issue.view",
     *     "reminder.send"
     * }, example="issue.edit", description="Specific permission.")
     */
    public $permission;
}
