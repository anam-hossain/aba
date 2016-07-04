<?php

namespace Anam\Aba\Test;

use \Exception;
use Anam\Aba\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidationFailed()
    {
        $this->expectException(Exception::class);

        Validator::validate($this->inaccurateData(), ['bsb', 'account_number', 'amount', 'reference']);
    }

    public function testMissingFieldsValidationException()
    {
        $this->expectException(Exception::class);

        Validator::validate($this->inaccurateData(), ['bsb', 'account_number', 'indicator', 'account_name', 'reference']);
    }

    public function testValidationIsPassed()
    {
        $this->assertTrue(Validator::validate($this->detailData(), ['bsb', 'account_number', 'reference', 'account_name']));
    }

    public function testValidateTransactionCode()
    {
        $this->assertTrue(Validator::validateTransactionCode(50));
    }

    public function testWrongTransactionCode()
    {
        $this->expectException(Exception::class);

        Validator::validateTransactionCode(100);
    }

    public function testValidateProcessDate()
    {
        $this->assertTrue(Validator::validateProcessDate('060616'));
    }

    public function testWrongProcessDate()
    {
        $this->expectException(Exception::class);

        Validator::validateProcessDate('29172016');
    }

    public function testNumericNumber()
    {
        $this->assertTrue(Validator::validateNumeric(200.00));
    }

    protected function detailData()
    {
        return [
            'bsb' => '111-111', // bsb with hyphen
            'account_number' => '999999999',
            'account_name'  => 'Jhon doe',
            'reference' => 'Payroll number',
            'transaction_code'  => '53',
            'amount' => '250.87'
        ];
    }

    protected function inaccurateData()
    {
        return [
            'bsb' => '111111', // bsb with hyphen
            'account_number' => '99999999944',
            'reference' => 'Payroll number',
            'amount' => '250.87'
        ];
    }
}