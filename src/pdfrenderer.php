<?php
/**
 * Pdf Renderer plugin for Craft CMS 3.x
 *
 * Trigger a new PDF to be rendered for a Thirdway product
 *
 * @link      http://bletchley.co
 * @copyright Copyright (c) 2018 Andy Skogrand
 */

namespace bletchley\pdfrenderer;

use bletchley\pdfrenderer\fields\Pdfresource as pdfresourcefield;

use \Datetime;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\events\ElementEvent;
use craft\events\ElementStructureEvent;
use craft\services\Elements;

use yii\base\Event;

use bletchley\pdfrenderer\jobs\PurgeCacheJob;

/**
 * Class pdfrenderer
 *
 * @author    Andy Skogrand
 * @package   pdfrenderer
 * @since     1.0.0
 *
 */

/**
* 0: A PDF has not been generated
* 1: PDF Saved
* 2: An error occurred saving the PDF
* 3: A PDF is being generated
*/

class pdfrenderer extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var pdfrenderer
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = pdfresourcefield::class;
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            function ($event) {
                $pdfUrl = Craft::$app->config->general->pdfServiceUrl;
                if(
                    $pdfUrl
                    && (int)$event->element->productPdfStatus === 3
                    && $event->element->enabled
                    && $event->element->id
                    && $event->element->uri
                ) {
                    \Craft::$app->getQueue()->delay(60)->push(new PurgeCacheJob([
                            'id' => $event->element->id,
                            'path' => $pdfUrl . 'pdf/' . $event->element->id . "/" . $event->element->uri
                        ]
                    ));
                }
            }
        );

        Craft::info(
            Craft::t(
                'pdfrenderer',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
