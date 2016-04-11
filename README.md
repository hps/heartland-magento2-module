![image](heartland-logo.png)
### Welcome to Heartland Magento2 Extension:

This project is in BETA. It works but you should be aware that there may be some rough code and some not so expected results. It is also an MVP. That is a Minimum Viable Product.

### Prerequisites:

We have laid out some basic requirements that you should absolutely have before working with this extension:

* Magento v2.0.2+ (at the time of this development effort this was the current release of Magento2)
* Experience in debugging if problems arise.
* SSH access to server as file system owner of web directory
* Keys for Magento connect
    https://www.magentocommerce.com/magento-connect/customerdata/secureKeys/list/
* Keys for your developer or live Heartland account
    https://developer.heartlandpaymentsystems.com/SecureSubmit/ Once you have created an account or signed in you can view available keys by navigating to Account->Profile. It is not necessary to have a live merchant account to have working certification keys.

### Feature list:

First a little about why we think you will be interested in our beta. Heartland supports several industry leading platforms. Magento2 is one of many.
Let's review what is and isn't ready for use:

* Checkout
* Card Saving
* Online Refund

What isn't ready yet:

* Ability to delete saved cards

With a heartland integration:

* You no longer have to worry about the burdens of PCI compliance because card data never touches your server.
* The exchange of sensitive information occurs directly between the consumer and Heartland Payment Systems through our Portico Gateway.
* Our convention-based jQuery plugin streamlines this process so you don’t have to worry about obtaining tokens. The plugin handles that request and appends the resulting token to your form before it posts.

### Lets get to it:

If you're still with us, you have decided that this sounds like something you want to be an early adopter. Remember you must SSH into your server as the web directory owner.

#### Installation:

From your base Magento2 directory -> app -> code (you may have to create this directory)

Copy the `HPS` directory from this repository to your `app/code` directory. From the base Magento2 directory (instructions assume Ubuntu 14+)

    rm -rf var/cache/* var/page_cache/* var/generation/* /var/di pub/static/adminhtml pub/static/frontend var/report/*

The following commands should work even in windows with the forward slash swapped for back.

    php bin/magento cache:clean
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    php bin/magento setup:static-content:deploy

Navigate to your admin logon. If you need the path you can retrieve it (web install usually sets this other than admin)

    php bin/magento info:adminuri

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

