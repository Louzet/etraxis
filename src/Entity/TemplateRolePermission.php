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
use eTraxis\Dictionary\SystemRole;
use eTraxis\Dictionary\TemplatePermission;
use Webinarium\PropertyTrait;

/**
 * Template permission for system role.
 *
 * @ORM\Table(name="template_role_permissions")
 * @ORM\Entity
 *
 * @property-read Template $template   Template.
 * @property-read string   $role       System role.
 * @property-read string   $permission Permission granted to the role for this template.
 */
class TemplateRolePermission implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_ROLE       = 'role';
    public const JSON_PERMISSION = 'permission';

    /**
     * @var Template
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="rolePermissionsCollection")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="permission", type="string", length=20)
     */
    protected $permission;

    /**
     * Constructor.
     *
     * @param Template $template
     * @param string   $role
     * @param string   $permission
     */
    public function __construct(Template $template, string $role, string $permission)
    {
        if (!SystemRole::has($role)) {
            throw new \UnexpectedValueException('Unknown system role: ' . $role);
        }

        if (!TemplatePermission::has($permission)) {
            throw new \UnexpectedValueException('Unknown permission: ' . $permission);
        }

        $this->template   = $template;
        $this->role       = $role;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_ROLE       => $this->role,
            self::JSON_PERMISSION => $this->permission,
        ];
    }
}
