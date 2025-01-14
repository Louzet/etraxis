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

namespace eTraxis\CommandBus\Command\Fields\CommandTrait;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for "duration" field commands.
 *
 * @property int $minimum Amount of minutes from 0:00 till 999999:59.
 * @property int $maximum Amount of minutes from 0:00 till 999999:59.
 * @property int $default Amount of minutes from 0:00 till 999999:59.
 */
trait DurationCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d{1,6}:[0-5][0-9]$/")
     *
     * @Groups("api")
     * @API\Property(type="string", example="23:59", description="Minimum value.")
     */
    public $minimum;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d{1,6}:[0-5][0-9]$/")
     *
     * @Groups("api")
     * @API\Property(type="string", example="23:59", description="Maximum value.")
     */
    public $maximum;

    /**
     * @Assert\Regex("/^\d{1,6}:[0-5][0-9]$/")
     *
     * @Groups("api")
     * @API\Property(type="string", example="23:59", description="Default value.")
     */
    public $default;
}
