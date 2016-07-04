<?php

namespace Anam\Aba\Test;

use PHPUnit\Framework\TestCase;
use Anam\Aba\Aba;

class AbaTest extends TestCase
{
    protected $aba;

    public function __construct()
    {
        $this->aba = new Aba();
    }

    public function testAddDescriptiveRecord()
    {
        $expectedDescriptiveString = '0                 01CBA       FOO BAR CORPORATION       301500PAYROLL     290616                                        ';

        $descriptveString = $this->aba->addDescriptiveRecord($this->descriptiveData());

        // Total descriptve record would be 120 characters
        // remove line break
        $descriptveString = substr($descriptveString, 0, 120);

        $this->assertEquals($expectedDescriptiveString, $descriptveString);
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
    }

    public function testAddFileTotalRecord()
    {
        $expectedFileTotalString = '7999-999            000002508700000250870000000000                        000001                                        ';
        
        $this->aba->addDescriptiveRecord($this->descriptiveData());
        $this->aba->addDetailRecord($this->detailData());

        $filTotalString = $this->aba->addFileTotalRecord();

        // Total detail record would be 120 characters
        // remove line break
        $filTotalString = substr($filTotalString, 0, 120);

        $this->assertEquals($expectedFileTotalString, $filTotalString);
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