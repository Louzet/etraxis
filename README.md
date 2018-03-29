[![PHP](https://img.shields.io/badge/PHP-7.1%2B-blue.svg)](https://php.net/migration71)

eTraxis is an issue tracking system with ability to set up an unlimited number of customizable workflows.
It can be used to track almost anything, though the most popular cases are a *bug tracker* and a *helpdesk system*.

### Features

* Custom workflows
* Fine-tuned permissions
* History of events and changes
* Filters and views
* Attachments
* Project metrics
* Authentication through Bitbucket, GitHub or Google
* Authentication through Active Directory (LDAP)
* MySQL and PostgreSQL support
* Localization and multilingual support
* Mobile-friendly web interface
* and more...

### Prerequisites

* [PHP](https://php.net/)
* [Composer](https://getcomposer.org/)

### Install

```bash
composer install
./bin/console doctrine:database:create
./bin/console doctrine:schema:create
./bin/console server:run
```

### Development

```bash
./bin/phpunit --coverage-html=var/coverage
```
