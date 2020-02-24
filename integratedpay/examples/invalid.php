<?php

$invalid_merchant_id =  <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<SecurePayMessage>
  <Status>
    <statusCode>504</statusCode>
    <statusDescription>Invalid merchant ID</statusDescription>
  </Status>
</SecurePayMessage>
XML;