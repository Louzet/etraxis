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

namespace eTraxis\TemplatesDomain\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * Template.
 *
 * @ORM\Table(
 *     name="templates",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"project_id", "name"}),
 *         @ORM\UniqueConstraint(columns={"project_id", "prefix"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\TemplatesDomain\Model\Repository\TemplateRepository")
 * @Assert\UniqueEntity(fields={"project", "name"}, message="template.conflict.name")
 * @Assert\UniqueEntity(fields={"project", "prefix"}, message="template.conflict.prefix")
 *
 * @property-read int     $id               Unique ID.
 * @property-read Project $project          Project of the template.
 * @property      string  $name             Name of the template.
 * @property      string  $prefix           Prefix of the template (used as a prefix in ID of issues,
 *                                          created using this template).
 * @property      string  $description      Optional description of the template.
 * @property      int     $criticalAge      When a issue remains opened more than this amount of days
 *                                          it is displayed in red in the list of issues.
 * @property      int     $frozenTime       When a issue is closed a user cannot change its state anymore,
 *                                          but one still can modify its fields, add comments and attach files.
 *                                          If frozen time is specified it will be allowed to modify issue this
 *                                          amount of days after its closure. After that issue will become read-only.
 *                                          If this attribute is not specified, issue will never become read-only.
 * @property      bool    $isLocked         Whether the template is locked for edition.
 */
class Template
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_PREFIX      = 5;
    public const MAX_DESCRIPTION = 100;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="templatesCollection")
     * @ORM\JoinColumn(name="project_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $project;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=5)
     */
    protected $prefix;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="critical_age", type="integer", nullable=true)
     */
    protected $criticalAge;

    /**
     * @var int
     *
     * @ORM\Column(name="frozen_time", type="integer", nullable=true)
     */
    protected $frozenTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_locked", type="boolean")
     */
    protected $isLocked;

    /**
     * Creates new template in the specified project.
     *
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
}
