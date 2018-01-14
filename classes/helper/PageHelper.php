<?php namespace Lovata\Toolbox\Classes\Helper;

use Cms\Classes\Theme;
use Cms\Classes\Page as CmsPage;

use October\Rain\Support\Traits\Singleton;

/**
 * Class PageHelper
 * @package Lovata\Toolbox\Classes\Helper
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class PageHelper
{
    use Singleton;

    /** @var  */
    protected $obTheme;

    /** @var array|CmsPage[] */
    protected $arPageList = [];

    /** @var array */
    protected $arCachedData = [];

    /**
     * Get component list with URL params for page
     * @param string $sPageCode
     * @param string $sComponentName
     * @param string $sParamName
     *
     * @return array
     */
    public function getUrlParamList($sPageCode, $sComponentName, $sParamName = 'slug')
    {
        $sCacheKey = implode('_', [$sPageCode, $sComponentName, $sParamName]);
        if($this->hasCache($sCacheKey)) {
            return $this->getCachedData($sCacheKey);
        }

        $arResult = [];
        if(empty($sPageCode) || empty($sComponentName) || empty($sParamName)) {
            return $arResult;
        }

        //Get component list
        $arComponentList = $this->getFullComponentList($sPageCode);
        if(empty($arComponentList)) {
            return $arResult;
        }

        foreach ($arComponentList as $sKey => $arPropertyList) {

            if(!preg_match('%^'.$sComponentName.'%', $sKey)) {
                continue;
            }

            if(empty($arPropertyList) || !isset($arPropertyList[$sParamName])) {
                continue;
            }

            /*
             * Extract the routing parameter name
             * eg: {{ :someRouteParam }}
             */
            if (!preg_match('/^\{\{([^\}]+)\}\}$/', $arPropertyList['slug'], $arMatches)) {
                continue;
            }

            $sValue = trim($arMatches[1]);
            $sValue = ltrim($sValue, ':');
            $arResult[] = $sValue;
        }

        $this->setCachedData($sCacheKey, $arResult);

        return $arResult;
    }

    /**
     * Init class data
     */
    protected function init()
    {
        $this->obTheme = Theme::getActiveTheme();
    }

    /**
     * Get component list for page
     * @param string $sPageCode
     *
     * @return array
     */
    protected function getFullComponentList($sPageCode)
    {
        if($this->hasCache($sPageCode)) {
            return $this->getCachedData($sPageCode);
        }

        //Get page object
        $obPage = $this->getPageObject($sPageCode);
        if(empty($obPage) || empty($obPage->settings) || !isset($obPage->settings['components'])) {
            return [];
        }

        //Get component list
        $arPageComponentList = $obPage->settings['components'];
        $this->setCachedData($sPageCode, $arPageComponentList);

        return $arPageComponentList;
    }

    /**
     * Get page object
     * @param string $sPageCode
     * @return CmsPage|null
     */
    protected function getPageObject($sPageCode)
    {
        if(isset($this->arPageList[$sPageCode])) {
            return $this->arPageList[$sPageCode];
        }

        if(empty($sPageCode) || empty($this->obTheme)) {
            return null;
        }

        $this->arPageList[$sPageCode] = CmsPage::loadCached($this->obTheme, $sPageCode);

        return $this->arPageList[$sPageCode];
    }

    /**
     * Get cached data
     * @param string $sKey
     * @return mixed|null
     */
    protected function getCachedData($sKey)
    {
        if(isset($this->arCachedData[$sKey])) {
            return $this->arCachedData[$sKey];
        }

        return null;
    }

    /**
     * Set cached data
     * @param string $sKey
     * @param mixed $obValue
     */
    protected function setCachedData($sKey, $obValue)
    {
        $this->arCachedData[$sKey] = $obValue;
    }

    /**
     * @param string $sKey
     * @return bool
     */
    protected function hasCache($sKey)
    {
        return isset($this->arCachedData[$sKey]);
    }
}