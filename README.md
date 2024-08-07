
![image](heartland-logo.png)
### !Important Note! - This extension has not been updated to provide support for the latest versions of Magento / Adobe Commerce. Our go-forward multi-gateway offering can be found [here](https://commercemarketplace.adobe.com/realexpayments-module-payment-gateway.html#description)

### Prerequisites:

We have laid out some basic requirements that you should absolutely have before working with this extension:

* Magento v2.1.x (at the time of this development effort this was the current release of Magento2)
* Experience in debugging if problems arise.
* SSH access to server as file system owner of web directory
* Keys for [Magento connect](https://marketplace.magento.com/customer/accessKeys/list/)
* Keys for your developer or live [Heartland account](https://developer.heartlandpaymentsystems.com/Account/KeysAndCredentials)
     Once you have created an account or signed in you can view available keys by navigating to Menu->API Keys & Credentials. It is not necessary to have a live merchant account to have working certification keys.

### Feature list:

Heartland supports several industry leading platforms. Magento2 is one of many.
Let's review what is ready for use:

* Checkout
* Card Saving
* Online Auth/Capture(invoice)
* Online Partial Capture
* Administrative Card delete for customer
* Online Charge
* Online Refund

With a Heartland integration:

* You no longer have to worry about the burdens of PCI compliance because card data never touches your server.
* The exchange of sensitive information occurs directly between the consumer and Heartland Payment Systems through our Portico Gateway.
* Our convention-based jQuery plugin streamlines this process so you don’t have to worry about obtaining tokens. The plugin handles that request and appends the resulting token to your form before it posts.

### Lets get to it:

If you're still with us, you have decided that this sounds like something you want to be an early adopter. Remember you must SSH into your server as the web directory owner.

#### Installation:
Please review the updated [Magento 2.1.x requirements](http://devdocs.magento.com/guides/v2.1/install-gde/system-requirements.html). It is critical that your installation meets all of these before continuing

* While it was unclear if a WAMP stack was ever supported, Magento 2.1.x now officially only supports a Linux x86-64 deployment.
* While the documentation suggests that setting up a swap file if you have less than 2 GB, we found that there were failures during the basic Magento 2 installation when the server had less than 2 GB. 
* While Magento2 documentation on [versioning](http://devdocs.magento.com/guides/v2.1/architecture/versioning.html) indicates the difference between 2.0.x and 2.1.x should be backward compatible changes 2.1.x no longer allows php 5.5.22 or greater.
[2.0.x](http://devdocs.magento.com/guides/v2.0/install-gde/system-requirements.html) vs [2.1.x](http://devdocs.magento.com/guides/v2.1/install-gde/system-requirements-tech.html)

##### Latest system requirements

There are now 2 options for installing our heartland-magento2-module.

* [PHP script] (HPS_Installer.php) The script will check system requirements for you and locate your magento instalation and create a customized ssh script to execute. If there is a problem it will tell you what the issue might be.
Just SSH to your server as a user part of the web servers group and call this line
```
wget https://raw.githubusercontent.com/hps/heartland-magento2-module/master/HPS_Installer.php && php -f HPS_Installer.php | tee -a HPS_Heartland.log && sh HPS_Install.sh | tee -a HPS_Heartland.log
```
* Manual

##### Manual Installation:
Clone this repo:
```
git clone https://github.com/hps/heartland-magento2-module.git
```

From your base Magento2 directory -> app -> code (you may have to create this directory) [Magento2 Documentation](http://devdocs.magento.com/guides/v2.1/architecture/archi_perspectives/components/modules/mod_intro.html).

Install Dependencies with Composer:
```
composer require hps/heartland-php
```

Copy the `HPS` directory from this repository to your `app/code` directory. From the base Magento2 directory (instructions assume Ubuntu 14+)
```
    cd ${Magento2Instalation}
    rm -rf var/cache/*
    rm -rf var/page_cache/*
    rm -rf var/generation/*
    rm -rf var/di
    rm -rf pub/static/adminhtml
    rm -rf pub/static/frontend
    rm -rf var/report/*
```
The following commands should work from your Magento 2 installation directory .
```
    cd ${Magento2Instalation}
    php bin/magento cache:clean
    composer require hps/heartland-php
    php bin/magento module:enable HPS_Heartland
    php bin/magento setup:upgrade  --keep-generated
    php bin/magento setup:di:compile
    php bin/magento setup:static-content:deploy
```
Navigate to your admin logon. If you need the path you can retrieve it (web install usually sets this other than admin)
```
php bin/magento info:adminuri
```

This will echo out the path

## Configure your store:

Open Store Configuration

![image](configNav.png)

Expand Sales and navigate to Payment Methods.

![image](pMethod.png)

Fill out the form as per the instructions on the screen. Please be aware that you can change the title to anything you would like to appear on your checkout page for the consumer to see.

![image](cHeartland.png)

### Support or Contact

Having trouble? Check out our [documentation](https://developer.heartlandpaymentsystems.com/SecureSubmit/Documentation) or [contact support](https://developer.heartlandpaymentsystems.com/SecureSubmit/Support) and we’ll help you sort it out.

