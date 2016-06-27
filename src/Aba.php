<?php

namespace Anam\Aba;

use Anam\Aba\Validation\Validator;
use \Exception;

class Aba
{
    /**
     * Descriptive record type 0. 
     * 
     * @const string
     */
    const DESCRIPTIVE_RECORD = '0';

    /**
     * Detail Record Type 1.
     * There are three detail record types 1, 2 and 3.
     * Only type 1 is used for batch tranactions
     * 
     * @const string
     */
    const DETAIL_RECORD = '1';

    /**
     * Record type 7 
     * 
     * @const string
     */
    const FILE_TOTAL_RECORD = '7';

    /**
     * The APCA standard string to generate ABA file
     * 
     * @var string
     */
    protected $abaFileContent = '';

    /**
     * Total number of the transactions
     * 
     * @var integer
     */
    protected $totalTransactions = 0;

    /**
     *  Credit total amount
     *  
     * @var float
     */
    protected $totalCreditAmount = 0;

    /**
     * Debit total amount
     * 
     * @var float
     */
    protected $totalDebitAmount = 0;

    /**
     * Descriptive record
     * 
     * @var array
     */
    protected $descriptiveRecord;

    /**
     * Descriptive or file header string
     * 
     * @var string
     */
    protected $descriptiveString = '';

    /**
     * Detail string
     * 
     * @var string
     */
    protected $detailString = '';

    /**
     * File total string
     * 
     * @var string
     */
    protected $fileTotalString = '';

    /**
     * Alias of addDescriptiveRecord
     * 
     * @param  array  $record
     * @return string
     */
    public function addFileDetails(array $record)
    {
        return $this->addDescriptiveRecord($record);
    }

    /**
     * Generate descriptive record string
     * 
     * @param  array  $record
     * @return string
     */
    public function addDescriptiveRecord(array $record)
    {
        Validator::validate(
            $record,
            ['bsb', 'account_number', 'bank_name', 'user_name', 'user_number', 'remitter', 'description'],
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
        $this->descriptiveString = self::DESCRIPTIVE_RECORD;

        // Position 2-18 - Blank spaces
        $this->descriptiveString .= $this->addBlankSpaces(17);

        // Postition 19 - 20
        // Reel Sequence Number
        $this->descriptiveString .= '01';

        // Position 21 - 23
        // Bank Name
        $this->descriptiveString .= $record['bank_name'];

        // Position 24 - 30 - Blank spaces
        $this->descriptiveString .= $this->addBlankSpaces(7);

        // Position 31 - 56
        // User Name
        $this->descriptiveString .= $this->padString($record['user_name'], '26');

        // Postion 57 - 62
        // User Number (as allocated by APCA)
        $this->descriptiveString .= $this->padString($record['user_number'], '6', '0', STR_PAD_RIGHT);

        // Position 63 - 74
        // Description of entries
        $this->descriptiveString .= $this->padString($record['description'], '12');

        // Position 75 - 80
        // Processing date - Format (DDMMYY)
        $this->descriptiveString .= $record['process_date'];

        // Position 81-120 - Blank spaces
        $this->descriptiveString .= $this->addBlankSpaces(40);

        $this->descriptiveString .= $this->addLineBreak();

        return $this->descriptiveString;
    }

    /**
     * Alias of AddDetailRecord
     * 
     * @param  array  $record
     * @return string
     */
    public function addTransaction(array $transaction)
    {
        return $this->addDetailRecord($transaction);
    }

    /**
     * Generate detail record string
     * 
     * @param  array  $transaction
     * @return string
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
            ['bsb', 'account_number', 'indicator', 'account_name', 'reference'],
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
        $this->detailString .= self::DETAIL_RECORD;

        // BSB
        $this->detailString .= $transaction['bsb'];

        // Account Number
        $this->detailString .= $this->padString($transaction['account_number'], '9', ' ', STR_PAD_LEFT);

        // Indicator
        $this->detailString .= $transaction['indicator'];

        // Transaction Code
        $this->detailString .= $transaction['transaction_code'];

        // Transaction Amount
        $this->detailString .= $this->padString($this->dollarsToCents($transaction['amount']), '10', '0', STR_PAD_LEFT);

        // Account Name
        $this->detailString .= $this->padString($transaction['account_name'], '32');

        // Lodgement Reference
        $this->detailString .= $this->padString($transaction['reference'], '18', ' ', STR_PAD_LEFT);

        // Trace BSB
        // Bank (FI)/State/Branch and account number of User to enable retracing of the entry to its source if necessary
        $this->detailString .= $this->descriptiveRecord['bsb'];

        // Trace Account Number
        $this->detailString .= $this->padString($this->descriptiveRecord['account_number'], '9', ' ', STR_PAD_LEFT);

        // Remitter Name
        $this->detailString .= $this->padString($this->descriptiveRecord['remitter'], '16');

        // Withholding amount
        $this->detailString .= $this->padString($this->dollarsToCents($transaction['withholding_tax']), '8', '0', STR_PAD_LEFT);

        $this->detailString .= $this->addLineBreak();

        return $this->detailString;
    }

    /**
     * Generate file total string
     *
     * @return string
     */
    public function addFileTotalRecord()
    {
        $this->fileTotalString = self::FILE_TOTAL_RECORD;

        // BSB Format Filler
        // Must be '999-999'
        $this->fileTotalString .= '999-999';

        // 12 Blank spaces
        $this->fileTotalString .= $this->addBlankSpaces(12);

        // File net total amount
        $this->fileTotalString .= $this->padString($this->dollarsToCents($this->getNetTotal()), '10', '0', STR_PAD_LEFT);

        // File credit total amount
        $this->fileTotalString .= $this->padString($this->dollarsToCents($this->totalCreditAmount), '10', '0', STR_PAD_LEFT);

        // File debit total amount
        $this->fileTotalString .= $this->padString($this->dollarsToCents($this->totalDebitAmount), '10', '0', STR_PAD_LEFT);

        // Must be 24 blank spaces
        $this->fileTotalString .= $this->addBlankSpaces(24);

        // Number of records
        $this->fileTotalString .= $this->padString($this->totalTransactions, '6', '0', STR_PAD_LEFT);

        // Must be 40 blank spaces
        $this->fileTotalString .= $this->addBlankSpaces(40);

        return $this->fileTotalString;
    }

    /** 
     * Generate ABA file content
     *
     * @return string
    */
    public function generate()
    {
        $this->addFileTotalRecord();

        $this->abaFileContent = $this->descriptiveString . $this->detailString . $this->fileTotalString;

        return $this->abaFileContent;
    }

    /**
     * Download ABA file
     */
    public function download()
    {
        //
    }

    /**
     * Generate blank spaces
     * 
     * @param int  $number [description]
     * @return string
     */
    public function AddBlankSpaces($number)
    {
        return str_repeat(' ', $number);
    }

    /**
     * Pad a string to a certain length with another string
     * 
     * @param  string $value
     * @param  int $length
     * @param  string $padString
     * @param  int $type
     * @return string
     */
    public function padString($value, $length, $padString = ' ', $type = STR_PAD_RIGHT)
    {
        return str_pad(substr($value, 0, $length), $length, $padString, $type);
    }

    /**
     * Convert decimal points to cents
     * 
     * @param  float $amount
     * @return int
     */
    public function dollarsToCents($amount)
    {
        return $amount * 100;
    }

    /**
     * Check a transaction is debit or credit and sum the amount accordingly
     * 
     * @param  array  $transaction
     * @return void
     */
    protected function calculateDebitOrCreditAmount(array $transaction)
    {
        if ($transaction['transaction_code'] == Validator::$transactionCodes['externally_initiated_debit']) {
            $this->totalDebitAmount += $transaction['amount'];
        } else {
            $this->totalCreditAmount += $transaction['amount'];
        }
    }

    /**
     * Calculate net total
     * 
     * @return float
     */
    protected function getNetTotal()
    {
        return abs($this->totalCreditAmount - $this->totalDebitAmount);
    }

    /**
     * Line break
     *
     * @return  string
     */
    protected function addLineBreak()
    {
        return "\r\n";
    }
}
