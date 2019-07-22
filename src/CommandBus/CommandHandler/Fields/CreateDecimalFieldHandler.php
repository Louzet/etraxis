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

use eTraxis\CommandBus\Command\Fields\CreateDecimalFieldCommand;
use eTraxis\Entity\Field;

/**
 * Command handler.
 */
class CreateDecimalFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\DecimalHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateDecimalFieldCommand $command
     *
     * @return Field
     */
    public function handle(CreateDecimalFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
