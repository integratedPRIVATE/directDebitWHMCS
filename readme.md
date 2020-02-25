# Integrated Pay - WHMCS Direct Debit Module

## Description

Integrated Pay is a payment gateway module for WHMCS that adds the Direct Debit (payment to and from banks) to the platform. 
Admins and clients can input bank details, and attempt capture, integrated pay will handle creating and submitting payment requests to SecurePay and validating responses

Note, You must have a valid SecurePay merchant account in order to use this module.

---

## Installing

You will need to have a functioning WHMCS environment running in order to install this

 * Download this repository to your local machine
 * Copy the file `integratedpay.php` and the folder `integratedpay` to the WHMCS gateway folder path:
``` 
WHMCS/modules/gateway/
```
 * Once that is done simply navigate to your payment gateway menu in the admin section of the platform.
 * * Setup > Payments > Payment Gateways
 * Navigate to `All Payment Gateways` and select `Integrated Pay` from the list

---

## Configuring

Integrated Pay comes with 3 config options that you can set in the admin system of WHMCS

 > Note that "Display Name," is simply for backwards compatibility and doesn't affect the behaviour of the module

 * Merchant ID
 * * This is the ID set up by you in your SecurePay merchant page, it is used to authenticate with SecurePay before autorizing transactions, please take care to check if this is correct
 * Transaction Password
 * * As with Merchant ID, this is set in your SecurePay merchant page and is passed to SecurePay to validate your credentials, if incorrect payment will simply not go through
 * Test Mode
 * * This is by default unticked, please only tick this if you wish to use the testing platform provided by SecurePay for developers, make sure to have this unticked in active environments as no payments will go through if this is turned on

> The options "Show on order Form," and "Convert To For Processing," are provided by WHMCS and do not have any impact over the behaviour of Integrated Pay.

---

## Authors

* **Matthew Alexander** - *Initial work*

## License

 * [GNU General Public License](LICENSE)
 * Copyright 2020 © <a href="https://www.integratedcapital.io/" >Integrated Capital</a>
