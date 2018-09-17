# Advanced Audit Tool

Drupal 8 auditor tool developed by Adyax.


## Installation

### 1. Prerequisites
To be able to setup this module on your project you will need following list of tools:
* composer
* Drupal installation via composer

### Installation process

* Update your project `composer.json` file and add following lines to the `repositories` block:
```
{
     "type": "vcs",
     "url": "git@code.adyax.com:Auditor/adv_audit.git"
 }
```
the resulting block should look like:

```
 "repositories": [
        {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
        {
            "type": "vcs",
            "url": "git@code.adyax.com:Auditor/adv_audit.git"
        }
    ],
```
* Run `composer require drupal/adv_audit` command.
* Specify your gitlab credentials (project hase private status for now)
* Module will be installed to module/contrib directory with all the dependencies in project `vendor` folder

