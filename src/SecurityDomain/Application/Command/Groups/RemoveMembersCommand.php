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

namespace eTraxis\SecurityDomain\Application\Command\Groups;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Removes specified users to the group.
 *
 * @property int   $id    Group ID.
 * @property int[] $users User IDs.
 */
class RemoveMembersCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $id;

    /**
     * @Assert\NotBlank
     * @Assert\Type(type="array")
     * @Assert\Count(min="1", max="100")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Regex("/^\d+$/")
     * })
     */
    public $users;
}
