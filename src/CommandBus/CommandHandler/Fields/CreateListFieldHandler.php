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

use eTraxis\CommandBus\Command\Fields\CreateListFieldCommand;
use eTraxis\Entity\Field;

/**
 * Command handler.
 */
class CreateListFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\ListHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateListFieldCommand $command
     *
     * @return Field
     */
    public function handle(CreateListFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
