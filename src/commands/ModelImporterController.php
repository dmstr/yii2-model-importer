<?php
/**
 * @link http://www.diemeisterei.de/
 * @copyright Copyright (c) 2017 diemeisterei GmbH, Stuttgart
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace dmstr\importer;

use yii\console\Controller;
use yii\db\ActiveRecord;
use yii\helpers\Console;


/**
 * Import data to crud
 *
 * @package dmstr\importer
 * @author Elias Luhr <e.luhr@herzogkommunikation.de>
 */
class ModelImporterController extends Controller
{

    /**
     * @var string Name of enclosing node
     */
    public $nodeName = 'item';

    /**
     *  Import XML Files
     *
     * Hint:
     *  - Escape backslashes in modelClass attribute
     *  - Use --interactive set to 0 to run command without a break
     *
     * @param string $filePath Path to .xml file
     * @param string $modelClass Name of the class in which the XML will be converted to
     *
     * @throws \yii\base\InvalidParamException
     */
    public function actionXml($filePath, $modelClass)
    {
        // check if model exists
        if (!class_exists($modelClass)) {
            $this->stderr("Class '{$modelClass}' does not exist" . PHP_EOL, Console::FG_RED);
            exit;
        }

        // get alias if alias is used
        $filePath = \Yii::getAlias($filePath, false);

        // check if file exists
        if (!is_file($filePath)) {
            $this->stderr('File does not exist' . PHP_EOL, Console::FG_RED);
            exit;
        }

        // PHPDoc var type \SimpleXMLElement just to satisfy PhpStorm's error hints where xpath() is called
        /** @var \SimpleXMLElement[]|bool|\SimpleXMLElement $xmlObjects */
        $xmlObjects = simplexml_load_file($filePath, \SimpleXMLElement::class, LIBXML_NOBLANKS);

        // check file has valid xml in it
        if ($xmlObjects === false) {
            $this->stderr('Content is not valid XML' . PHP_EOL, Console::FG_RED);
            exit;
        }

        $xmlObjects = $xmlObjects->xpath('//' . $this->nodeName);

        // check xml has nodes named like defined variable
        if (empty($xmlObjects)) {
            $this->stderr("Node '{$this->nodeName}' not found" . PHP_EOL, Console::FG_RED);
            exit;
        }

        // set item counter
        $itemCounter = 0;
        foreach ($xmlObjects as $xmlObject) {
            // increase counter here for output
            $itemCounter++;
            /** @var ActiveRecord $model */
            $model = new $modelClass();

            /** @var \SimpleXMLElement $attributeValue */
            foreach ($xmlObject as $attributeName => $attributeValue) {

                if($model->hasProperty($attributeName) && $model->canSetProperty($attributeName)){
                    // remove outer xml node
                    $value = preg_replace("/<\\/?{$attributeName}(.|\\s)*?>/",null,$attributeValue->asXML());
                    $model->$attributeName = $value;
                } else {
                    $this->stdout("Can not set attribute '{$attributeName}' for item {$itemCounter}" . PHP_EOL, Console::FG_BLUE);
                }
            }

            // check if model is saved
            if (!$model->save()) {

                $this->stderr("Item {$itemCounter} can not be saved" . PHP_EOL, Console::FG_RED);

                foreach ($model->getErrors() as $attribute => $errorMessages) {
                    $this->stderr($attribute . ':' . PHP_EOL, Console::FG_RED);
                    foreach ((array)$errorMessages as $errorMessage) {
                        $this->stderr(' - ' . $errorMessage . PHP_EOL, Console::FG_RED);
                    }
                }

                if (!$this->confirm('Continue import?', true)) {
                    $this->stderr(PHP_EOL . 'Stopped import' . PHP_EOL, Console::FG_RED);
                    exit;
                }
                // decrease for correct output if import failed
                $itemCounter--;
            }
        }


        $this->stdout(PHP_EOL . "Saved {$itemCounter} item" . ($itemCounter !== 1 ? 's' : null) . PHP_EOL,Console::FG_GREEN);
    }


    /**
     *  Import JSON Files
     *
     * Hint:
     *  - Escape backslashes in modelClass attribute
     *  - Use --interactive set to 0 to run command without a break
     *
     * @param string $filePath Path to .xml file
     * @param string $modelClass Name of the class in which the XML will be converted to
     *
     * @throws \yii\base\InvalidParamException
     */
    public function actionJson($filePath,$modelClass) {

        // check if model exists
        if (!class_exists($modelClass)) {
            $this->stderr("Class '{$modelClass}' does not exist" . PHP_EOL, Console::FG_RED);
            exit;
        }

        // get alias if alias is used
        $filePath = \Yii::getAlias($filePath, false);

        // check if file exists
        if (!is_file($filePath)) {
            $this->stderr('File does not exist' . PHP_EOL, Console::FG_RED);
            exit;
        }

        // check return value
        $itemCollection = json_decode(file_get_contents($filePath));

        // check file has valid json in it
        if ($itemCollection === null) {
            $this->stderr('Content is not valid JSON' . PHP_EOL, Console::FG_RED);
            exit;
        }

        // set item counter
        $itemCounter = 0;
        foreach ((array)$itemCollection as $item) {
            // increase counter here for output
            $itemCounter++;
            /** @var ActiveRecord $model */
            $model = new $modelClass();

            foreach ((array)$item as $attributeName => $attributeValue) {
                if($model->hasProperty($attributeName) && $model->canSetProperty($attributeName)){
                    $model->$attributeName = $attributeValue;
                } else {
                    $this->stdout("Can not set attribute '{$attributeName}' for item {$itemCounter}" . PHP_EOL, Console::FG_BLUE);
                }
            }

            // check if model is saved
            if (!$model->save()) {

                $this->stderr("Item {$itemCounter} can not be saved" . PHP_EOL, Console::FG_RED);

                foreach ($model->getErrors() as $attribute => $errorMessages) {
                    $this->stderr($attribute . ':' . PHP_EOL, Console::FG_RED);
                    foreach ((array)$errorMessages as $errorMessage) {
                        $this->stderr(' - ' . $errorMessage . PHP_EOL, Console::FG_RED);
                    }
                }

                if (!$this->confirm('Continue import?', true)) {
                    $this->stderr(PHP_EOL . 'Stopped import' . PHP_EOL, Console::FG_RED);
                    exit;
                }
                // decrease for correct output if import failed
                $itemCounter--;
            }
        }

        $this->stdout(PHP_EOL . "Saved {$itemCounter} item" . ($itemCounter !== 1 ? 's' : null) . PHP_EOL,Console::FG_GREEN);
    }
}