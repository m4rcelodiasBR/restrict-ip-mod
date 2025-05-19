## Nota 
Esse módulo foi modificado para atender as necessidades do pessoal que estão com dificuldades
em fazer o módulo funcionar em seus respectivos ambientes intranet.
Alterado por 2SG(PD) Marcelo Dias

Deve ser criado um node com o alias "acesso-restrito" para o bom funcionamento. Boa sorte

# Download
Baixe o arquivo compactado restrict-ip.zip

# Whitelisting IPs in settings.php

## Table of Contents
* About
* Installation
* Configuration
  * Drush commands
  * Modules that expand on restrict IP
* Questions and Answers
* Support

## About

This module allows administrators to restrict access to the site to an
administrator defined set of IP addresses. Anyone trying to access the site
from an IP address not in the list of allowed IP addresses will be redirected
to access denied page with the message "Your address is not in the list of
allowed IP addresses". No blocks will be rendered, and no JavaScript will be
added to the page. This will happen for any and all pages these users try to
access. The module also has various configuration options to whitelist or
blacklist pages, bypass IP checking by role, and alter the output when the
user is blocked.

## Installation

Install the UI Patterns Setting module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420.

## Configuration

Settings can be whitelisted in settings.php by creating the following array
containing each IP addresses to be whitelisted.

$config['restrict_ip.settings']['ip_whitelist'] = [
'111.111.111.1',
'111.111.111.2',
];

### Drush commands

If you don't have access/permissions on settings files, you can also use a restrict_ip drush command :

`drush ripd [enable or disable]`

### Modules that expand on restrict IP

1. [IP-based Determination of a Visitor's Country](https://www.drupal.org/project/ip2country)
> This module uses a visitor's IP address to identify the geographical location (country)
> of the user. The module makes this determination and stores the result as an
> ISO 3166 2-character country code in the Drupal $user object, but otherwise
> has no effect on the operation of your site. The intent is simply to provide
> the information for use by other modules. A function is also provided for you
> to perform your own lookup, to use in your own manner. Features
> include automatic updates of the IP-country database and admin spoofing
> of an arbitrary IP or Country for testing purposes.


## Questions and Answers

1. Question: I locked myself out of my site, what do I do?
    * Answer: Open add the following line to sites/default/settings.php
    * $config['restrict_ip.settings']['enable'] = FALSE;
    * You will now be able to access the site (as will anyone else).
    * Go to the configuration page, and fix your settings.
    * Remove this code when you are finished.
2. Question: I want to redirect users to a different site when they do not have
   access. How can I do this?
    * Answer: You will need to write some code for this. The easiest way is in your
      theme (though it can also be done in a custom module).
    * First, you'll need the theme key of your theme. This will be the name of the
      folder that your theme lies in. My examples below will be for a fictional theme,
      jaypanify.
    * Next, open up the file named template.php in your theme. If this file does not
      exist, you can create it (though most themes will already have it). At the
      bottom of this file, add the hook below, changing 'hook' to your actual theme
      key, and changing the link from Google.com to the URL to which you want to
      redirect your users:

function hook_restrict_ip_access_denied_page_alter(&$page)
{
$response = new \Symfony\Component\HttpFoundation\RedirectResponse('https://www.google.com/');
$response->send();
}

* Clear your Drupal cache, and the redirect should work.

3. Question: I want to alter the access denied page. How can I do this?
    * Answer: It depends on whether you want to add to this page, remove from it, or
      alter it. However, whichever it is, all methods work under the same principle.
    * First, you'll need the key of your theme (see the previous question for
      directions on how to get this). Next you'll need to open template.php, and add
      one of the following snippets to this file. Note that you will need to change
      'hook' to the actual name of your theme.

***

ADDING to the page:
The following example shows how to add a new element to the page

function hook_restrict_ip_access_denied_page_alter(&$page)
{
  // note that 'some_key' is arbitrary, and you should use something descriptive
  // instead.
  $page['some_key'] = [
    '#markup' => t('This is some markup which I would like to add'),
    '#prefix' => '<p>', // You can use any tag you want here,
    '#suffix' => '</p>', // the closing tag needs to match the #prefix tag
  ];
}

***

REMOVING from the page:
The following example shows how to remove the logout link for logged-in users
who are denied access

function hook_restrict_ip_access_denied_page_alter(&$page)
{
  if(isset($page['logout_link']))
  {
    unset($page['logout_link']);
  }
}

***

ALTERING the page:
As of the time of writing, this module provides the following keys that can be
altered:
* access_denied
* contact_us (may not exist, depending on the module configuration)
* logout_link (may not exist, depending on the module configuration)
* login_link (may not exist, depending on the module configuration)

The following example shows how to change the text of the 'access denied'
message to your own custom message

function jaypanify_restrict_ip_access_denied_page_alter(&$page)
{
  if(isset($page['access_denied']))
  {
  	$page['access_denied'] = t('My custom access denied message');
  }
}

## Support

If you are having troubles with any of the above recipes, please open a support
ticket in the module issue queue:
https://drupal.org/project/issues/search/restrict_ip

Please list the following:

1) What you are trying to achieve
2) The code you have tried that isn't working
3) A description of how it is not working
