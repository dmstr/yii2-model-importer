Yii2 Model Importer
====

A Yii2 Extention to import data to our cruds

## Installation via composer

```
composer require dmstr/yii2-model-importer
```
## Settings after installation 

####Add the module to your application config
```
<?php
    ...
    'modules' => [
        'model-importer' => [
            'class' => dmstr\importer\Module::class
        ]
    ]
    ...
```
####And the console controller to your console controllerMap config
```
<?php
    ...
    'controllerMap' => [
            'sitefusion' => dmstr\commands\ModelImporterController::class
    ]
    ...
```