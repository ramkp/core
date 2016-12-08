<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.7.0 (http://modulestudio.de).
 */

namespace Zikula\RoutesModule\Helper;

use Zikula\Bundle\CoreBundle\CacheClearer;
use Zikula\Bundle\CoreBundle\DynamicConfigDumper;
use Zikula\ExtensionsModule\Api\VariableApi;

class MultilingualRoutingHelper
{
    /**
     * @var VariableApi
     */
    private $variableApi;

    /**
     * @var DynamicConfigDumper
     */
    private $configDumper;

    /**
     * @var CacheClearer
     */
    private $cacheClearer;

    /**
     * @var string
     */
    private $locale;

    /**
     * MultilingualRoutingHelper constructor.
     * @param VariableApi         $variableApi
     * @param DynamicConfigDumper $configDumper
     * @param CacheClearer        $cacheClearer
     * @param string              $locale
     */
    public function __construct(VariableApi $variableApi, DynamicConfigDumper $configDumper, CacheClearer $cacheClearer, $locale)
    {
        $this->variableApi = $variableApi;
        $this->configDumper = $configDumper;
        $this->cacheClearer = $cacheClearer;
        $this->locale = $locale;
    }

    /**
     * Reloads the multilingual routing settings by reading system variables and checking installed languages.
     *
     * @return bool
     */
    public function reloadMultilingualRoutingSettings()
    {
        $defaultLocale = $this->variableApi->getSystemVar('language_i18n', $this->locale);
        $installedLanguages = \ZLanguage::getInstalledLanguages();
        $isRequiredLangParameter = $this->variableApi->getSystemVar('languageurl', 0);

        $this->configDumper->setConfiguration('jms_i18n_routing', [
            'default_locale' => $defaultLocale,
            'locales' => $installedLanguages,
            'strategy' => $isRequiredLangParameter ? 'prefix' : 'prefix_except_default'
        ]);

        $this->cacheClearer->clear('symfony');

        return true;
    }
}