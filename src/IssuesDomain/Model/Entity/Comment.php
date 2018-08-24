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

namespace eTraxis\IssuesDomain\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webinarium\PropertyTrait;

/**
 * Issue comment.
 *
 * @ORM\Table(
 *     name="comments",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"event_id"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\IssuesDomain\Model\Repository\CommentRepository")
 *
 * @property-read int    $id        Unique ID.
 * @property-read Event  $event     Event which the comment has been posted by.
 * @property      string $body      Comment's body.
 * @property      bool   $isPrivate Whether the comment is private.
 */
class Comment
{
    use PropertyTrait;

    // Constraints.
    public const MAX_VALUE = 4000;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $event;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     */
    protected $body;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_private", type="boolean")
     */
    protected $isPrivate;

    /**
     * Creates comment.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
