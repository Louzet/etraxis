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

namespace eTraxis\Entity;

use Doctrine\ORM\Mapping as ORM;
use eTraxis\Dictionary\EventType;
use Webinarium\PropertyTrait;

/**
 * Event.
 *
 * @ORM\Table(
 *     name="events",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"type", "issue_id", "user_id", "created_at"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\EventRepository")
 *
 * @property-read int      $id        Unique ID.
 * @property-read string   $type      Type of the event (see the "EventType" dictionary).
 * @property-read Issue    $issue     Issue of the event.
 * @property-read User     $user      Initiator of the event.
 * @property-read int      $createdAt Unix Epoch timestamp when the event has happened.
 * @property-read null|int $parameter Event parameter.
 */
class Event implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_TYPE      = 'type';
    public const JSON_USER      = 'user';
    public const JSON_TIMESTAMP = 'timestamp';
    public const JSON_ASSIGNEE  = 'assignee';
    public const JSON_FILE      = 'file';
    public const JSON_ISSUE     = 'issue';
    public const JSON_STATE     = 'state';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     */
    protected $type;

    /**
     * @var Issue
     *
     * @ORM\ManyToOne(targetEntity="Issue", inversedBy="eventsCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="issue_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $issue;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false, referencedColumnName="id")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="created_at", type="integer")
     */
    protected $createdAt;

    /**
     * @var int Event parameter. Depends on event type as following:
     *          ISSUE_CREATED      - Initial state (foreign key to "State" entity)
     *          ISSUE_EDITED       - NULL (not used)
     *          STATE_CHANGED      - New state (foreign key to "State" entity)
     *          ISSUE_REOPENED     - New state of the reopened issue (foreign key to "State" entity)
     *          ISSUE_CLOSED       - New state of the closed issue (foreign key to "State" entity)
     *          ISSUE_ASSIGNED     - Responsible user (foreign key to "User" entity)
     *          ISSUE_SUSPENDED    - NULL (not used)
     *          ISSUE_RESUMED      - NULL (not used)
     *          PUBLIC_COMMENT     - NULL (not used)
     *          PRIVATE_COMMENT    - NULL (not used)
     *          FILE_ATTACHED      - Attached file (foreign key to "File" entity)
     *          FILE_DELETED       - Deleted file (foreign key to "File" entity)
     *          DEPENDENCY_ADDED   - Dependency issue (foreign key to "Issue" entity)
     *          DEPENDENCY_REMOVED - Dependency issue (foreign key to "Issue" entity)
     *
     * @ORM\Column(name="parameter", type="integer", nullable=true)
     */
    protected $parameter;

    /**
     * Creates new event.
     *
     * @param string   $type
     * @param Issue    $issue
     * @param User     $user
     * @param null|int $parameter
     */
    public function __construct(string $type, Issue $issue, User $user, ?int $parameter = null)
    {
        $this->type      = $type;
        $this->issue     = $issue;
        $this->user      = $user;
        $this->createdAt = time();
        $this->parameter = $parameter;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $main = [
            self::JSON_TYPE      => $this->type,
            self::JSON_USER      => [
                User::JSON_ID       => $this->user->id,
                User::JSON_EMAIL    => $this->user->email,
                User::JSON_FULLNAME => $this->user->fullname,
            ],
            self::JSON_TIMESTAMP => $this->createdAt,
        ];

        $extras = [
            EventType::ISSUE_CREATED      => self::JSON_STATE,
            EventType::STATE_CHANGED      => self::JSON_STATE,
            EventType::ISSUE_REOPENED     => self::JSON_STATE,
            EventType::ISSUE_CLOSED       => self::JSON_STATE,
            EventType::ISSUE_ASSIGNED     => self::JSON_ASSIGNEE,
            EventType::FILE_ATTACHED      => self::JSON_FILE,
            EventType::FILE_DELETED       => self::JSON_FILE,
            EventType::DEPENDENCY_ADDED   => self::JSON_ISSUE,
            EventType::DEPENDENCY_REMOVED => self::JSON_ISSUE,
        ];

        if (array_key_exists($this->type, $extras)) {
            $extra = [$extras[$this->type] => $this->parameter];
        }
        else {
            $extra = [];
        }

        return $main + $extra;
    }
}
