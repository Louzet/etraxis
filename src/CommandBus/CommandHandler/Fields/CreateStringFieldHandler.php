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

namespace eTraxis\CommandBus\CommandHandler\Fields;

use eTraxis\CommandBus\Command\Fields\CreateStringFieldCommand;
use eTraxis\Entity\Field;

/**
 * Command handler.
 */
class CreateStringFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\StringHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateStringFieldCommand $command
     *
     * @return Field
     */
    public function handle(CreateStringFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
