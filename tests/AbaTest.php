<?php

namespace Anam\Aba\Test;

use PHPUnit\Framework\TestCase;
use Anam\Aba\Aba;

class AbaTest extends TestCase
{
    public function testAddDescriptiveRecord()
    {
        $expectedString = '0                 01CBA       FOO BAR CORPORATION       301500PAYROLL     290616                                       ';

        $aba = new Aba();

        $descriptveString = $aba->addFileDetails([
            'bsb' => '062-111', // bsb
            'account_number' => '111111111', // account number
            'bank_name' => 'CBA', // bank name
            'user_name' => 'FOO BAR CORPORATION', // Account name, up to 26 characters
            'remitter' => 'FOO BAR', // Remitter
            'user_number' => '301500', // direct entry id for CBA
            'description' => 'PAYROLL', // description
            'process_date'  => '290616' // DDMMYY
        ]);

        // Total descriptve record would be 120 characters
        // remove line break
        $descriptveString = substr($descriptveString, 0, 119);

        $this->assertEquals($expectedString, $descriptveString);
    }
}