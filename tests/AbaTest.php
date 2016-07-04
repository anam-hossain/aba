<?php

namespace Anam\Aba\Test;

use PHPUnit\Framework\TestCase;
use Anam\Aba\Aba;

class AbaTest extends TestCase
{
    use PrivateAndProtectedMethodsAccessibleTrait;

    protected $aba;

    public function __construct()
    {
        $this->aba = new Aba();
    }

    public function testAddDescriptiveRecord()
    {
        $expectedDescriptiveString = '0                 01CBA       FOO BAR CORPORATION       301500PAYROLL     290616                                        ';

        $descriptiveString = $this->aba->addDescriptiveRecord($this->descriptiveData());

        // Total descriptve record would be 120 characters
        // remove line break
        $descriptiveString = substr($descriptiveString, 0, 120);

        $this->assertEquals($expectedDescriptiveString, $descriptiveString);

        return $descriptiveString;
    }

    public function testAddDetailRecord()
    {
        $expectedDetailString = '1111-111999999999 530000025087Jhon doe                            Payroll number062-111111111111FOO BAR         00000000';

        $this->aba->addDescriptiveRecord($this->descriptiveData());

        $detailString = $this->aba->addDetailRecord($this->detailData());

        // Total detail record would be 120 characters
        // remove line break
        $detailString = substr($detailString, 0, 120);

        $this->assertEquals($expectedDetailString, $detailString);

        return $detailString;
    }

    public function testAddFileTotalRecord()
    {
        $expectedFileTotalString = '7999-999            000002508700000250870000000000                        000001                                        ';
        
        $this->aba->addDescriptiveRecord($this->descriptiveData());
        $this->aba->addDetailRecord($this->detailData());

        $fileTotalString = $this->aba->addFileTotalRecord();

        // Total detail record would be 120 characters
        // remove line break
        $fileTotalString = substr($fileTotalString, 0, 120);

        $this->assertEquals($expectedFileTotalString, $fileTotalString);

        return $fileTotalString;
    }

    public function testGenerate()
    {
        $expectedDescriptiveString = '0                 01CBA       FOO BAR CORPORATION       301500PAYROLL     290616                                        ';
        $expectedDetailString = '1111-111999999999 530000025087Jhon doe                            Payroll number062-111111111111FOO BAR         00000000';
        $expectedFileTotalString = '7999-999            000002508700000250870000000000                        000001                                        ';
                
        $this->aba->addDescriptiveRecord($this->descriptiveData());
        $this->aba->addDetailRecord($this->detailData());

        $abaString = $this->aba->generate();

        $this->assertContains($expectedDescriptiveString, $abaString, "Testing descriptive record string is valid");
        $this->assertContains($expectedDetailString, $abaString, "Testing detail record string is valid");
        $this->assertContains($expectedFileTotalString, $abaString, "Testing file total record string is valid");
    }

    public function testAddBlankSpaces()
    {
        $this->assertEquals('   ', $this->aba->addBlankSpaces(3));
    }

    public function testPadString()
    {
        $expected = 'Foo Bar   ';

        $this->assertEquals($expected, $this->aba->padString('Foo Bar', 10));
    }

    public function testDollarsToCents()
    {
        $expected = 25065;

        $this->assertEquals($expected, $this->aba->dollarsToCents(250.65));
    }

    public function testGetNetTotal()
    {
        $this->aba->addDescriptiveRecord($this->descriptiveData());
        $this->aba->addDetailRecord($this->detailData());

        $expected = 250.87;

        $total = $this->invokeMethod($this->aba, 'getNetTotal');

        $this->assertEquals($expected, $total);
    }

    public function testAddLineBreak()
    {
        $this->assertEquals("\r\n", $this->invokeMethod($this->aba, 'addLineBreak'));
    }

    public function testCalculateDebitOrCreditAmount()
    {
        $expectedCreditAmount = 250.87;

        $this->invokeMethod($this->aba, 'calculateDebitOrCreditAmount', [$this->detailData()]);

        $this->assertEquals($expectedCreditAmount, $this->aba->getTotalCreditAmount());

        $this->assertNotEquals(0, $this->aba->getTotalCreditAmount());

        $this->assertEquals(0, $this->aba->getTotalDebitAmount());
    }

    protected function descriptiveData()
    {
        return [
            'bsb' => '062-111', // bsb
            'account_number' => '111111111', // account number
            'bank_name' => 'CBA', // bank name
            'user_name' => 'FOO BAR CORPORATION', // Account name, up to 26 characters
            'remitter' => 'FOO BAR', // Remitter
            'user_number' => '301500', // direct entry id for CBA
            'description' => 'PAYROLL', // description
            'process_date'  => '290616' // DDMMYY
        ];
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
}