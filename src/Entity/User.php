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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use eTraxis\Dictionary\AccountProvider;
use eTraxis\Dictionary\Locale;
use eTraxis\Dictionary\Theme;
use eTraxis\Dictionary\Timezone;
use LazySec\Entity\DisableAccountTrait;
use LazySec\Entity\LockAccountTrait;
use LazySec\Entity\ResetPasswordTrait;
use LazySec\Entity\UserTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Webinarium\PropertyTrait;

/**
 * User.
 *
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"account_provider", "account_uid"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\Repository\UserRepository")
 * @Assert\UniqueEntity(fields={"email"}, message="user.conflict.email")
 *
 * @property-read int         $id          Unique ID.
 * @property      string      $email       Email address.
 * @property      null|string $password    Password.
 * @property      string      $fullname    Full name.
 * @property      null|string $description Optional description of the user.
 * @property      bool        $isAdmin     Whether the user has administrator privileges.
 * @property      AccountInfo $account     User's account.
 * @property      string      $locale      User's locale (see the "Locale" dictionary).
 * @property      string      $theme       User's theme (see the "Theme" dictionary).
 * @property      string      $timezone    User's timezone (see the "Timezone" dictionary).
 * @property-read Group[]     $groups      List of groups the user is member of.
 */
class User implements \JsonSerializable, EncoderAwareInterface, UserInterface
{
    use PropertyTrait;
    use UserTrait;
    use DisableAccountTrait;
    use LockAccountTrait;
    use ResetPasswordTrait;

    // Roles.
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER  = 'ROLE_USER';

    // Constraints.
    public const MAX_EMAIL       = 254;
    public const MAX_FULLNAME    = 50;
    public const MAX_DESCRIPTION = 100;

    // JSON properties.
    public const JSON_ID          = 'id';
    public const JSON_EMAIL       = 'email';
    public const JSON_FULLNAME    = 'fullname';
    public const JSON_DESCRIPTION = 'description';
    public const JSON_ADMIN       = 'admin';
    public const JSON_DISABLED    = 'disabled';
    public const JSON_LOCKED      = 'locked';
    public const JSON_PROVIDER    = 'provider';
    public const JSON_LOCALE      = 'locale';
    public const JSON_THEME       = 'theme';
    public const JSON_TIMEZONE    = 'timezone';
    public const JSON_OPTIONS     = 'options';

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
     * @ORM\Column(name="email", type="string", length=254, unique=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", nullable=true)
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="fullname", type="string", length=50)
     */
    protected $fullname;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * @var string User's role (see "User::ROLE_..." constants).
     *
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected $role;

    /**
     * @var AccountInfo
     *
     * @ORM\Embedded(class="AccountInfo")
     */
    protected $account;

    /**
     * @var array User's settings.
     *
     * @ORM\Column(name="settings", type="json_array", nullable=true)
     */
    protected $settings;

    /**
     * @var ArrayCollection|Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", mappedBy="membersCollection")
     * @ORM\OrderBy({"name": "ASC", "project": "ASC"})
     */
    protected $groupsCollection;

    /**
     * Creates new user.
     */
    public function __construct()
    {
        $this->role             = self::ROLE_USER;
        $this->account          = new AccountInfo();
        $this->groupsCollection = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [$this->role];
    }

    /**
     * Checks whether the account is loaded from 3rd party provider.
     *
     * @return bool
     */
    public function isAccountExternal(): bool
    {
        return $this->account->provider !== AccountProvider::ETRAXIS;
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove in 4.1
     */
    public function getEncoderName()
    {
        switch (mb_strlen($this->password)) {
            case 32:
                return 'legacy.md5';
            case 28:
                return 'legacy.sha1';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_ID          => $this->id,
            self::JSON_EMAIL       => $this->email,
            self::JSON_FULLNAME    => $this->fullname,
            self::JSON_DESCRIPTION => $this->description,
            self::JSON_ADMIN       => $this->isAdmin,
            self::JSON_DISABLED    => !$this->isEnabled(),
            self::JSON_LOCKED      => !$this->isAccountNonLocked(),
            self::JSON_PROVIDER    => $this->account->provider,
            self::JSON_LOCALE      => $this->locale,
            self::JSON_THEME       => $this->theme,
            self::JSON_TIMEZONE    => $this->timezone,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'isAdmin' => function (): bool {
                return $this->role === self::ROLE_ADMIN;
            },

            'locale' => function (): string {
                return $this->settings['locale'] ?? Locale::FALLBACK;
            },

            'theme' => function (): string {
                return $this->settings['theme'] ?? Theme::FALLBACK;
            },

            'timezone' => function (): string {
                return $this->settings['timezone'] ?? Timezone::FALLBACK;
            },

            'groups' => function (): array {
                return $this->groupsCollection->getValues();
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'password' => function (?string $password): void {
                $this->password = $password;
                $this->clearResetToken();
            },

            'isAdmin' => function (bool $value): void {
                $this->role = $value ? self::ROLE_ADMIN : self::ROLE_USER;
            },

            'locale' => function (string $value): void {
                if (Locale::has($value)) {
                    $this->settings['locale'] = $value;
                }
            },

            'theme' => function (string $value): void {
                if (Theme::has($value)) {
                    $this->settings['theme'] = $value;
                }
            },

            'timezone' => function (string $value): void {
                if (Timezone::has($value)) {
                    $this->settings['timezone'] = $value;
                }
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccountBeLocked(): bool
    {
        return !$this->isAccountExternal();
    }
}
