<?php
/**
 * This document describes an example request and reponse XML messsage for securepay 
 */


$request =  <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SecurePayMessage>
  <MessageInfo>
    <messageID>2aedc3d9-fdad-1204-a15f-168cea59808a</messageID>
    <messageTimestamp>20202102071806998000+600</messageTimestamp>
    <timeoutValue>60</timeoutValue>
    <apiVersion>spxml-3.0</apiVersion>
  </MessageInfo>
  <MerchantInfo>
    <merchantID>ABC0001</merchantID>
    <password>abc123</password>
  </MerchantInfo>
  <RequestType>Periodic</RequestType>
  <Periodic>
    <PeriodicList count="1">
      <PeriodicItem ID="1">
        <actionType>add</actionType>
        <clientID>WillBoop</clientID>
        <DirectEntryInfo>
          <bsbNumber>000111</bsbNumber>
          <accountNumber>123478965</accountNumber>
          <accountName>ANZ</accountName>
          <creditFlag>no</creditFlag>
        </DirectEntryInfo>
        <amount>100</amount>
        <periodicType>4</periodicType>
      </PeriodicItem>
    </PeriodicList>
  </Periodic>
</SecurePayMessage>
XML;


$response = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<SecurePayMessage>
  <MessageInfo>
    <messageID>2aedc3d9-fdad-1204-a15f-168cea59808a</messageID>
    <messageTimestamp>20202102181807178000+660</messageTimestamp>
    <apiVersion>spxml-3.0</apiVersion>
  </MessageInfo>
  <RequestType>Periodic</RequestType>
  <MerchantInfo>
    <merchantID>ABC0001</merchantID>
  </MerchantInfo>
  <Status>
    <statusCode>0</statusCode>
    <statusDescription>Normal</statusDescription>
  </Status>
  <Periodic>
    <PeriodicList count="1">
      <PeriodicItem ID="1">
        <actionType>add</actionType>
        <clientID>WillBoop</clientID>
        <responseCode>00</responseCode>
        <responseText>successful</responseText>
        <successful>yes</successful>
        <DirectEntryInfo>
          <bsbNumber>000111</bsbNumber>
          <accountNumber>123478965</accountNumber>
          <accountName>ANZ</accountName>
          <ddaAck>no</ddaAck>
          <creditFlag>no</creditFlag>
        </DirectEntryInfo>
        <amount>100</amount>
        <currency>AUD</currency>
        <periodicType>4</periodicType>
        <paymentInterval/>
        <numberOfPayments/>
      </PeriodicItem>
    </PeriodicList>
  </Periodic>
</SecurePayMessage>
XML;