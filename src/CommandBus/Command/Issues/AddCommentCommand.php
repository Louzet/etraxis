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

namespace eTraxis\CommandBus\Command\Issues;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Adds new comment to specified issue.
 *
 * @property int    $issue   Issue ID.
 * @property string $body    Comment body.
 * @property bool   $private Whether the comment is private.
 */
class AddCommentCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $issue;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="10000")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=10000, example="Lorem ipsum", description="Text of the comment.")
     */
    public $body;

    /**
     * @Assert\NotNull
     *
     * @Groups("api")
     * @API\Property(type="boolean", example=false, description="Whether should be private.")
     */
    public $private;
}
