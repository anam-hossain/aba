<?php

namespace Anam\Aba;

use Anam\Aba\Validation\Validator;
use \Exception;

class Aba
{
    const DESCRIPTIVE_RECORD = '0';
    const DETAIL_RECORD = '1';
    const FILE_TOTAL_RECORD = '7';

    protected $abaFileContent = '';

    protected $totalTransactions = 0;

    protected $totalCreditAmount = 0;

    protected $totalDebitAmount = 0;

    protected $countRecords = 0;

    protected $descriptiveRecord;

    protected $descriptiveString = '';

    protected $detailString = '';

    protected $fileTotalString = '';

    public function addFileDetails(array $record)
    {
        return $this->addDescriptiveRecord($record);
    }

    public function addDescriptiveRecord(array $record)
    {
        Validator::validate(
            $record,
            ['bsb', 'account_number', 'bank_name', 'user_name', 'remitter'],
            'Descriptive'
        );

        // Verify processing date
        // The date format must be DDMMYY
        Validator::validateProcessDate($record['process_date']);
        
        // Save the record to use it later
        $this->descriptiveRecord = $record;

        // Lets build the descriptive record string        
        // Position 1
        // Record Type
        $descriptiveString = self::DESCRIPTIVE_RECORD;

        // Position 2-18 - Blank spaces
        $descriptiveString .= $this->addBlankSpaces(17);

        // Postition 19 - 20
        // Reel Sequence Number
        $descriptiveString .= '01';

        // Position 21 - 23
        // Bank Name
        $descriptiveString .= $record['bank_name'];

        // Position 24 - 30 - Blank spaces
        $descriptiveString .= $this->addBlankSpaces(7);

        // Position 31 - 56
        // User Name
        $descriptiveString .= $this->padString($record['user_name'], '26');

        // Postion 57 - 62
        // User Number (as allocated by APCA)
        $descriptiveString .= $this->padString($record['user_number'], '6', '0', STR_PAD_RIGHT);

        // Position 63 - 74
        // Description of entries
        $descriptiveString .= $this->padString($record['description'], '12');

        // Position 75 - 80
        // Processing date - Format (DDMMYY)
        $descriptiveString .= $record['process_date'];

        // Position 81-120 - Blank spaces
        $descriptiveString .= $this->addBlankSpaces(40);

        $descriptiveString .= $this->addLineBreak();

        return $descriptiveString;
    }

    public function addTransaction(array $transaction)
    {
        return $this->addDetailRecord($transaction);
    }

    /**
     * 
     */
    public function addDetailRecord(array $transaction)
    {
        if (! isset($transaction['indicator'])) {
            $transaction['indicator'] = ' ';
        }

        if (! isset($transaction['withholding_tax'])) {
            $transaction['withholding_tax'] = 0;
        }

        Validator::validate(
            $transaction,
            ['bsb', 'account_number', 'indicator', 'account_name' 'reference'],
            'Detail'
        );

        Validator::validateTransactionCode($transaction['transaction_code']);

        Validator::validateNumeric($transaction['amount']);

        Validator::validateNumeric($transaction['withholding_tax']);

        // Calculate debit or credit amount
        $this->calculateDebitOrCreditAmount($transaction);

        // Increment total transactions
        $this->totalTransactions++;

        // Generate detail record string for a transaction
        // Record Type
        $detailString = self::DETAIL_RECORD;

        // BSB
        $detailString .= $transaction['bsb'];

        // Account Number
        $detailString .= $this->padString($transaction['account_number'], '9', ' ', STR_PAD_LEFT);

        // Indicator
        $detailString .= $transaction['indicator'];

        // Transaction Code
        $detailString .= $transaction['transaction_code'];

        // Transaction Amount
        $detailString .= $this->padString($this->dollarsToCents($transaction['amount']), '10', '0', STR_PAD_LEFT);

        // Account Name
        $detailString .= $this->padString($transaction['account_name'], '32');

        // Lodgement Reference
        $detailString .= $this->padString($transaction['reference'], '18', ' ', STR_PAD_LEFT);

        // Trace BSB
        // Bank (FI)/State/Branch and account number of User to enable retracing of the entry to its source if necessary
        $detailString .= $this->descriptiveRecord['bsb'];

        // Trace Account Number
        $detailString .= $this->padString($this->descriptiveRecord['account_number'], '9', ' ', STR_PAD_LEFT);

        // Remitter Name
        $detailString .= $this->padString($transaction['remitter'], '16');

        // Withholding amount
        $detailString .= $this->padString($this->dollarsToCents($transaction['withholding_tax']), '8', '0', STR_PAD_LEFT);

        $detailString .= $this->addLineBreak();

        return $detailString;
    }

    public function addFileTotalRecord()
    {
        $fileTotalString = self::FILE_TOTAL_RECORD;

        // BSB Format Filler
        // Must be '999-999'
        $fileTotalString .= '999-999';

        // 12 Blank spaces
        $fileTotalString .= $this->addBlankSpaces(12);

        // File net total amount
        $fileTotalString .= $this->padString($this->dollarsToCents($this->getNetTotal()), '10', '0', STR_PAD_LEFT);

        // File credit total amount
        $fileTotalString .= $this->padString($this->dollarsToCents($this->totalCreditAmount), '10', '0', STR_PAD_LEFT);

        // File debit total amount
        $fileTotalString .= $this->padString($this->dollarsToCents($this->totalDebitAmount), '10', '0', STR_PAD_LEFT);

        // Must be 24 blank spaces
        $fileTotalString .= $this->addBlankSpaces(24);

        // Number of records
        $fileTotalString .= $this->padString($this->totalTransactions, '6', '0', STR_PAD_LEFT);

        // Must be 40 blank spaces
        $fileTotalString .= $this->addBlankSpaces(40);

        return $fileTotalString;
    }

    public function generate()
    {
        
        $this->abaFileContent = $this->descriptiveString . $this->detailString . $this->fileTotalString;

        return $this->abaFileContent;
    }

    public function download()
    {
        //
    }

    public function AddBlankSpaces($number)
    {
        return str_repeat(' ', $number);
    }

    public function padString($value, $length, $padString = ' ', $type = STR_PAD_RIGHT)
    {
        return str_pad(substr($value, 0, $length), $length, $padString, $type);
    }

    public function dollarsToCents($amount)
    {
        return $amount * 100;
    }

    protected function calculateDebitOrCreditAmount(array $transaction)
    {
        if ($transaction['transaction_code'] == Validator::$transactionCodes['externally_initiated_debit']) {
            $this->totalDebitAmount += $transaction['amount'];
        } else {
            $this->totalCreditAmount += $transaction['amount'];
        }
    }

    protected function getNetTotal()
    {
        return abs($this->totalCreditAmount - $this->totalDebitAmount);
    }

    protected function addLineBreak()
    {
        return "\r\n";
    }
}
