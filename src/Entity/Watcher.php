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
use Webinarium\PropertyTrait;

/**
 * Issue watchers.
 *
 * @ORM\Table(name="watchers")
 * @ORM\Entity(repositoryClass="eTraxis\Repository\WatcherRepository")
 *
 * @property-read Issue $issue Issue.
 * @property-read User  $user  User.
 */
class Watcher implements \JsonSerializable
{
    use PropertyTrait;

    /**
     * @var Issue
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Issue")
     * @ORM\JoinColumn(name="issue_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $issue;

    /**
     * @var User
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="eTraxis\Entity\User")
     * @ORM\JoinColumn(name="user_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * Creates new watcher.
     *
     * @param Issue $issue
     * @param User  $user
     */
    public function __construct(Issue $issue, User $user)
    {
        $this->issue = $issue;
        $this->user  = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            User::JSON_ID       => $this->user->id,
            User::JSON_EMAIL    => $this->user->email,
            User::JSON_FULLNAME => $this->user->fullname,
        ];
    }
}
