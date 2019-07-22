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

namespace eTraxis\EventBus\Event;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\EventDispatcher\Event;
use Webinarium\DataTransferObjectTrait;

/**
 * Specified account successfully logged in.
 *
 * @property string $username Account's username.
 */
class LoginSuccessfulEvent extends Event
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="254")
     * @Assert\Email
     */
    public $username;
}
