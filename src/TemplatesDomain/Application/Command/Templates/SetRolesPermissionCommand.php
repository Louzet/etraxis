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

namespace eTraxis\TemplatesDomain\Application\Command\Templates;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Sets specified roles permission for the template.
 *
 * @property int      $id         Template ID.
 * @property string   $permission Template permission.
 * @property string[] $roles      Granted system roles.
 */
class SetRolesPermissionCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $id;

    /**
     * @Assert\NotBlank,
     * @Assert\Choice(callback={"eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission", "keys"}, strict=true)
     */
    public $permission;

    /**
     * @Assert\NotBlank
     * @Assert\Type(type="array")
     * @Assert\Count(min="0", max="100")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Choice(callback={"eTraxis\TemplatesDomain\Model\Dictionary\SystemRole", "keys"}, strict=true)
     * })
     */
    public $roles;
}