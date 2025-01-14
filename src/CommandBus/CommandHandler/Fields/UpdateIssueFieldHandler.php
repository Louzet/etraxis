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

use eTraxis\CommandBus\Command\Fields\UpdateIssueFieldCommand;

/**
 * Command handler.
 */
class UpdateIssueFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\IssueHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateIssueFieldCommand $command
     */
    public function handle(UpdateIssueFieldCommand $command): void
    {
        $this->update($command);
    }
}
