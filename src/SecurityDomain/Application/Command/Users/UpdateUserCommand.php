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

namespace eTraxis\SecurityDomain\Application\Command\Users;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified account.
 *
 * @property int    $id          User ID.
 * @property string $email       New email address.
 * @property string $fullname    New full name.
 * @property string $description New description.
 * @property bool   $admin       New role (whether has administrator permissions).
 * @property bool   $disabled    New status.
 * @property string $locale      New locale.
 * @property string $theme       New theme.
 * @property string $timezone    New timezone.
 */
class UpdateUserCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $id;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     */
    public $email;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $fullname;

    /**
     * @Assert\Length(max="100")
     */
    public $description;

    /**
     * @Assert\NotNull
     */
    public $admin;

    /**
     * @Assert\NotNull
     */
    public $disabled;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\SecurityDomain\Model\Dictionary\Locale", "keys"}, strict=true)
     */
    public $locale;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\SecurityDomain\Model\Dictionary\Theme", "keys"}, strict=true)
     */
    public $theme;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\SecurityDomain\Model\Dictionary\Timezone", "values"}, strict=true)
     */
    public $timezone;
}