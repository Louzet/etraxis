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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * State.
 *
 * @ORM\Table(
 *     name="states",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"template_id", "name"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\TemplatesDomain\Model\Repository\StateRepository")
 * @Assert\UniqueEntity(fields={"template", "name"}, message="state.conflict.name")
 *
 * @property-read int                     $id                Unique ID.
 * @property-read Template                $template          Template of the state.
 * @property      string                  $name              Name of the state.
 * @property-read string                  $type              Type of the state (see the "StateType" dictionary).
 * @property      string                  $responsible       Type of responsibility management (see the "StateResponsible" dictionary).
 * @property      State                   $nextState         Next state by default (optional).
 * @property-read StateRoleTransition[]   $roleTransitions   List of state role transitions.
 * @property-read StateGroupTransition[]  $groupTransitions  List of state group transitions.
 * @property-read StateResponsibleGroup[] $responsibleGroups List of responsible groups.
 */
class State
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME = 50;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Template
     *
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="statesCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=12)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="responsible", type="string", length=10)
     */
    protected $responsible;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumn(name="next_state_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $nextState;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="StateRoleTransition", mappedBy="fromState")
     */
    protected $roleTransitionsCollection;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="StateGroupTransition", mappedBy="fromState")
     */
    protected $groupTransitionsCollection;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="StateResponsibleGroup", mappedBy="state")
     */
    protected $responsibleGroupsCollection;

    /**
     * Creates new state in the specified template.
     *
     * @param Template $template
     * @param string   $type
     */
    public function __construct(Template $template, string $type)
    {
        if (!StateType::has($type)) {
            throw new \UnexpectedValueException('Unknown state type: ' . $type);
        }

        $this->template = $template;
        $this->type     = $type;

        $this->roleTransitionsCollection   = new ArrayCollection();
        $this->groupTransitionsCollection  = new ArrayCollection();
        $this->responsibleGroupsCollection = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'responsible' => function (): string {
                return $this->type === StateType::FINAL ? StateResponsible::REMOVE : $this->responsible;
            },

            'nextState' => function (): ?State {
                return $this->type === StateType::FINAL ? null : $this->nextState;
            },

            'roleTransitions' => function (): array {
                return $this->roleTransitionsCollection->getValues();
            },

            'groupTransitions' => function (): array {
                return $this->groupTransitionsCollection->getValues();
            },

            'responsibleGroups' => function (): array {
                return $this->responsibleGroupsCollection->getValues();
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'responsible' => function (string $value): void {
                if (StateResponsible::has($value)) {
                    $this->responsible = $value;
                }
                else {
                    throw new \UnexpectedValueException('Unknown responsibility type: ' . $value);
                }
            },

            'nextState' => function (?State $value): void {
                if ($value === null || $value->template === $this->template) {
                    $this->nextState = $value;
                }
                else {
                    throw new \UnexpectedValueException('Unknown state: ' . $value->name);
                }
            },
        ];
    }
}
