# Automatic Order Tasks For WooCommerce

Stable tag: 1.3.2 
Requires at least: 5.6  
Tested up to: 6.6 
Requires PHP: 7.4  
License: GPL v2 or later  
Tags: order status, send email, create post, shipping method, logging, custom order field, webhook, woocommerce, automatic actions
Contributors: pankado

## Description

Create automatic tasks that run whenever a WooCommerce order reaches an order status of your choice.

You can send emails, create posts, add custom order fields and much more. Each task is customizable, allowing you to insert and edit your own content. You can also add powerful tags that insert dynamic values such as order details, customer names and more.


### Features
 
-- Automatic Order Tasks --
+ **Send Email** - send a message to anyone with information about the order.
+ **Create Post** - insert your own content, you can also set the order's customer as the post author.
+ **Change Shipping Method** - switch to a new shipping method automatically.
+ **Log To File** - write a fully customizable log that you can view and download at any time.
+ **Custom Order Field** - add custom fields to your order with extra information.
+ **Send Webhook** - notify someone when an order reaches your chosen status.
+ **Trash Order** - automatically send the order to trash
Supporting the tasks above are powerful but easy to use tags that add dynamic values to your content.

### Usage

 1. In the WordPress admin area, put your cursor over the WooCommerce menu and select Automatic Order Tasks from the list that appears.
 2. On the following page, select the order status you would like to add a task to, then click the "New Task" button and select the option you would like.
 3. Configure the task to your liking. The purple buttons can be clicked to automatically insert dynamic tags into your content. Once finished, click the "Save Changes" button.

## Screenshots

1. Easily view and manage the tasks assigned to each order status
2. Customize the task and add your own content

## Frequently Asked Questions

### Does this plugin work with PHP 8?

Yes, it's actively tested on PHP versions 7.4 - 8.2

### Do you take suggestions?

Yes! Feel free to offer suggestions by emailing kontakt@pankado.com. While we may not be able to answer every email, we will read and consider every request. Please note that we do not offer support on said email address.

### Do you offer support?

Please refer to the WordPress support forum for this plugin to report any bug or performance related issues. A service with a more comprehensive level of support will be made available at a later date.

### Can I see the source code?

Yes, the source code can be found at https://github.com/pankado/automatic-order-tasks-for-woocommerce/

## Changelog ##

### 1.3.0 ###
- Added hooks and filters for various order tasks
- Minor bug fixes

### 1.2.2 ###

- Support for smaller screens on the setup page

### 1.2.1 ###

- Added Support for language translations
- Added Danish translation

### 1.2 ###

- Added 3 new dynamic tags: billing phone, billing company and order note

### 1.1 ###

- Each task now has a settings window, which can be opened by clicking on the "settings" link located in the upper right corner of a task panel. (next to the remove button)
- Added an option to restrict a task to only ever run once per order, even if the order reaches the same order status multiple times
- Minor changes in design and internal data structure

### 1.0 ###

- Official Release