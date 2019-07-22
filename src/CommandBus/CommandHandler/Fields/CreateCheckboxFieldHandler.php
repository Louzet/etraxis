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

use eTraxis\CommandBus\Command\Fields\CreateCheckboxFieldCommand;
use eTraxis\Entity\Field;

/**
 * Command handler.
 */
class CreateCheckboxFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\CheckboxHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateCheckboxFieldCommand $command
     *
     * @return Field
     */
    public function handle(CreateCheckboxFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
