# Ugrade to 4.0

This release adds support for PHP 8.0.

## BC BREAK: require `slm\queue` >= 3.0

Upgrading to `slm\queue` 3+ will require some changes, see [upgrade notes](https://github.com/JouwWeb/SlmQueue/blob/master/UPGRADE.md).

## BC BREAK: changed recover CLI command

The recover CLI can now be invoked with the following command:

```sh
vendor/bin/laminas slm-queue:doctrine:recover <queueName> [--executionTime=]
```
