# README

# Contents

- Introduction
- Prerequisites
- Rebranding
- Installing and configuring the module
- License

# Introduction

This CubeCart module provides an easy method to integrate with the payment gateway.
 - The httpdocs directory contains the files that need to be uploaded to the root directory of where CubeCart is installed
 - Supports CubeCart versions: **4.x+**

# Prerequisites

- The module requires the following prerequisites to be met in order to function correctly:
    - For a full list of requirements please see: https://www.cubecart.com/hosting-requirements

> Please note that we can only offer support for the module itself. While every effort has been made to ensure the payment module is complete and bug free, we cannot guarantee normal functionality if unsupported changes are made.

# Rebranding

To rebrand this module, complete the following steps:

1. In file `httpdocs/modules/gateway/PaymentNetwork/gateway.class.php` change the following:
	- Line 7: `const API_ENDPOINT_HOSTED = 'https://gateway.example.com/hosted/';` change this URL to your gateway URL we supply
2. Replace the logo.gif with your own logo in directory: `httpdocs/modules/gateway/PaymentNetwork/admin/`
3. In file `httpdocs/modules/gateway/PaymentNetwork/language/module.definitions.xml` change the following:
	- Line 11: `<string name="payment_page_url_default"><![CDATA[e.g. https://gateway.example.com/hosted/]]></string>` change this URL to your gateway URL we supply
	- Line 12: `<string name="payment_page_url_default_value"><![CDATA[https://gateway.example.com/hosted/]]></string>` change this URL to your gateway URL we supply
4. In file `httpdocs/modules/gateway/PaymentNetwork/config.xml` change the following:
	- Line 4: `	<uid>gateway-payment-network@cubecart.com</uid>` to your support email
	- Line 8: `<description><![CDATA[Payment Network Payment Gateway]]></description>` changing Payment Network Payment Gateway to your brand name
5. When downloading as a zip file, you can right-click and rename to remove the `Unbranded` text from the filename.


# Installing and configuring the module

1. Copy the contents of the httpdocs into your root directory of CubeCart. If you are prompted to overwrite or merge files, click 'Yes' to all
2. Log in to the Admin panel of your CubeCart Shop
3. Click 'Payment Gateway' under the 'Module' subheading in the left hand menu
4. Look for PaymentNetwork and click the checkbox to the left of the logo. Scroll down and click 'save'
5. From the same screen, click the 'edit' icon to the right of the PaymentNetwork module. Ensure that the 'Status' and 'Default' boxes are checked. Your payment gateway URL should correctly be set to 'https://gateway.example.com/hosted/' by default. Fill in the relevant information in the boxes shown and click 'save'
6. Your PaymentNetwork payment module is now installed!

License
----
MIT
