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

namespace eTraxis\CommandBus\Command\Users;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified account.
 *
 * @property int    $user        User ID.
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
    public $user;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=254, example="anna@example.com", description="Email address (RFC 5322).")
     */
    public $email;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=50, example="Anna Rodygina", description="Full name.")
     */
    public $fullname;

    /**
     * @Assert\Length(max="100")
     *
     * @Groups("api")
     * @API\Property(type="string", maxLength=100, example="very lovely daughter", description="Optional description.")
     */
    public $description;

    /**
     * @Assert\NotNull
     *
     * @Groups("api")
     * @API\Property(type="boolean", example=false, description="Whether should have administrator privileges.")
     */
    public $admin;

    /**
     * @Assert\NotNull
     *
     * @Groups("api")
     * @API\Property(type="boolean", example=false, description="Whether should be disabled.")
     */
    public $disabled;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Dictionary\Locale", "keys"}, strict=true)
     *
     * @Groups("api")
     * @API\Property(type="string", example="en_NZ", description="Locale (ISO 639-1 / ISO 3166-1).")
     */
    public $locale;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Dictionary\Theme", "keys"}, strict=true)
     *
     * @Groups("api")
     * @API\Property(type="string", example="azure", description="Theme.")
     */
    public $theme;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\Dictionary\Timezone", "values"}, strict=true)
     *
     * @Groups("api")
     * @API\Property(type="string", example="Pacific/Auckland", description="Timezone (IANA database value).")
     */
    public $timezone;
}
