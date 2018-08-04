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

namespace eTraxis\SharedDomain\Framework\Validator\Constraints;

use eTraxis\Tests\WebTestCase;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class DecimalRangeValidatorTest extends WebTestCase
{
    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = $this->client->getContainer()->get('validator');
    }

    public function testMissingOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Either option "min" or "max" must be given for constraint "eTraxis\\SharedDomain\\Framework\\Validator\\Constraints\\DecimalRange".');

        $constraint = new DecimalRange();

        $this->validator->validate('0', [$constraint]);
    }

    public function testInvalidMinOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "min" option given for constraint "eTraxis\\SharedDomain\\Framework\\Validator\\Constraints\\DecimalRange" is invalid.');

        $constraint = new DecimalRange([
            'min' => 'test',
        ]);

        $this->validator->validate('0', [$constraint]);
    }

    public function testInvalidMaxOption()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max" option given for constraint "eTraxis\\SharedDomain\\Framework\\Validator\\Constraints\\DecimalRange" is invalid.');

        $constraint = new DecimalRange([
            'max' => 'test',
        ]);

        $this->validator->validate('0', [$constraint]);
    }

    public function testBothOptions()
    {
        $constraint = new DecimalRange([
            'min' => '-10',
            'max' => '+10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('-10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    public function testMinOptionOnly()
    {
        $constraint = new DecimalRange([
            'min' => '-10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('-10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be -10 or more.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.01', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    public function testMaxOptionOnly()
    {
        $constraint = new DecimalRange([
            'max' => '+10',
        ]);

        $errors = $this->validator->validate('-11', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.01', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('10.01', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be +10 or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('10.00', [$constraint]);
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('test', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(null, [$constraint]);
        self::assertCount(0, $errors);
    }

    public function testCustomMinMessage()
    {
        $constraint = new DecimalRange([
            'min'        => '1',
            'minMessage' => 'The value must be >= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('0', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be >= 1.', $errors->get(0)->getMessage());
    }

    public function testCustomMaxMessage()
    {
        $constraint = new DecimalRange([
            'max'        => '10',
            'maxMessage' => 'The value must be <= {{ limit }}.',
        ]);

        $errors = $this->validator->validate('11', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value must be <= 10.', $errors->get(0)->getMessage());
    }

    public function testCustomInvalidMessage()
    {
        $constraint = new DecimalRange([
            'min'            => '1',
            'max'            => '10',
            'invalidMessage' => 'The value is invalid.',
        ]);

        $errors = $this->validator->validate('test', [$constraint]);
        self::assertNotCount(0, $errors);
        self::assertSame('The value is invalid.', $errors->get(0)->getMessage());
    }
}