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

namespace eTraxis\TemplatesDomain\Application\Command\Fields;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\TransactionalTestCase;

class UpdateTextFieldCommandTest extends TransactionalTestCase
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Description']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(10000, $facade->getMaximumLength());
        self::assertNull($facade->getDefaultValue());
        self::assertNull($facade->getPCRE()->check);
        self::assertNull($facade->getPCRE()->search);
        self::assertNull($facade->getPCRE()->replace);

        $command = new UpdateTextFieldCommand([
            'field'         => $field->id,
            'name'          => $field->name,
            'required'      => $field->isRequired,
            'maximumLength' => 2000,
            'defaultValue'  => 'How to reproduce:',
            'pcreCheck'     => '.+',
            'pcreSearch'    => 'search',
            'pcreReplace'   => 'replace',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame(2000, $facade->getMaximumLength());
        self::assertSame('How to reproduce:', $facade->getDefaultValue());
        self::assertSame('.+', $facade->getPCRE()->check);
        self::assertSame('search', $facade->getPCRE()->search);
        self::assertSame('replace', $facade->getPCRE()->replace);
    }
}