# ABA
Provides a simple way to generate an ABA file which is used by banks to allow for batch transactions.

## Features

- Simple API
- Framework agnostic

## Requirements

- PHP 5.4+

## Installation
`Aba` is available via Composer

```bash
$ composer require anam/aba
```

## Integrations

##### Laravel integrations
Although `Aba` is framework agnostic, it does support Laravel out of the box and comes with a Service provider and Facade for easy integration.

After you have installed the `Aba`, open the `config/app.php` file which is included with Laravel and add the following lines.

In the `$providers` array add the following service provider.

```php
Anam\Aba\AbaServiceProvider::class
```

Add the facade of this package to the `$aliases` array.

```php
'Aba' => Anam\Aba\Facades\Aba::class,
```

You can now use this facade in place of instantiating the converter yourself in the following examples.

## Usage

```php
use Anam\Aba\Aba;

$aba = new Aba();

// Descriptive record or file header
// The header information is included at the top of every ABA file
// and is used to describe your bank details.
$aba->addFileDetails([
    'bank_name' => 'CBA', // bank name
    'user_name' => 'Your account name', // Account name
    'bsb' => '062-111', // bsb with hyphen
    'account_number' => '101010101', // account number
    'remitter' => 'Name of remitter', // Remitter
    'user_number' => '301500', // User Number (as allocated by APCA). The Commonwealth bank default is 301500
    'description' => 'Payroll', // description
    'process_date'  => '270616' // DDMMYY - Date to be processed 
]);

// Add a transaction or Detail record
$aba->addTransaction([
    'bsb' => '111-111', // bsb with hyphen
    'account_number' => '999999999',
    'account_name'  => 'Jhon doe',
    'reference' => 'Payroll number',
    'transaction_code'  => '53',
    'amount' => '250.87'
]);

$abaFileContent = $aba->generate(); // Generate ABA string.

$aba->download();
```

###### Mutiple transactions
```php
$transactions = [
    [
        'bsb' => '111-111', // bsb with hyphen
        'account_number' => '999999999',
        'account_name'  => 'Jhon doe',
        'reference' => 'Payroll number',
        'transaction_code'  => '53',
        'amount' => '250.87'
    ],
    [
        'bsb' => '222-2222', // bsb with hyphen
        'account_number' => '888888888',
        'account_name'  => 'Foo Bar',
        'reference' => 'Rent',
        'transaction_code'  => '50',
        'amount' => '300'
    ]
];

foreach ($transactions as $transaction) {
    $aba->addTransaction($transaction);
}

$aba->generate();

$aba->download("Multiple-transactions");
```

#### Laravel example
```php
use Aba;

// Descriptive record or file header
// The header information is included at the top of every ABA file
// and is used to describe your bank details.
Aba::addFileDetails([]);

Aba::addTransaction([]);

Aba::generate();

Aba::download();
```
#### Appendix

##### Validation

<table cellpadding="5" cellspacing="0">
    <tbody>
        <tr>
            <td>Field</td>
            <td>Description</td>
        </tr>
        <tr>
            <td>Bank name</td>
            <td>Bank name must be 3 characters long and Capitalised. For example: CBA</td>
        </tr>
        <tr>
            <td>BSB</td>
            <td>The valid BSB format is XXX-XXX.</td>
        </tr>
        <tr>
            <td>Account number</td>
            <td>Account number must be up to 9 digits.</td>
        </tr>
        <tr>
            <td>User name (Descriptive record)</td>
            <td>User or preferred name must be letters only and up to 26 characters long.</td>
        </tr>
        <tr>
            <td>Account name (Detail record)</td>
            <td>Account name must be letters only and up to 32 characters long.</td>
        </tr>
        <tr>
            <td>User number</td>
            <td>User number which is allocated by APCA must be up to 6 digits long. The Commonwealth bank default is 301500.</td>
        </tr>
        <tr>
            <td>Description (Descriptive record)</td>
            <td>Description must be up to 12 characters long and letters only.</td>
        </tr>
        <tr>
            <td>Reference (Detail record)</td>
            <td>The reference must be up to 18 characters long. For example: Payroll number.</td>
        </tr>
        <tr>
            <td>Remitter</td>
            <td>The remitter must be letters only and up to 16 characters long.</td>
        </tr>
    </tbody>
</table>

##### Transaction codes
<table cellpadding="5" cellspacing="0">
    <tbody>
        <tr>
            <td>Code</td>
            <td>Transaction Description</td>
        </tr>
        <tr>
            <td>13</td>
            <td>Externally initiated debit items</td>
        </tr>
        <tr>
            <td>50</td>
            <td>Externally initiated credit items with the exception of those bearing Transaction Codes</td>
        </tr>
        <tr>
            <td>51</td>
            <td>Australian Government Security Interest</td>
        </tr>
        <tr>
            <td>52</td>
            <td>Family Allowance</td>
        </tr>
        <tr>
            <td>53</td>
            <td>Pay</td>
        </tr>
        <tr>
            <td>54</td>
            <td>Pension</td>
        </tr>
        <tr>
            <td>55</td>
            <td>Allotment</td>
        </tr>
        <tr>
            <td>56</td>
            <td>Dividend</td>
        </tr>
        <tr>
            <td>57</td>
            <td>Debenture/Note Interest</td>
        </tr>
    </tbody>
</table>

## Reference
- [http://www.apca.com.au/docs/default-source/payment-systems/becs_procedures.pdf](http://www.apca.com.au/docs/default-source/payment-systems/becs_procedures.pdf)
- [https://www.commbank.com.au/content/dam/robohelp/PDFS/commbiz_direct_credit_debit.pdf](https://www.commbank.com.au/content/dam/robohelp/PDFS/commbiz_direct_credit_debit.pdf)
- [https://www.cemtexaba.com/aba-format/cemtex-aba-file-format-details](https://www.cemtexaba.com/aba-format/cemtex-aba-file-format-details)

