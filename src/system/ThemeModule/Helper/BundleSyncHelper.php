<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ThemeModule\Helper;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Bundle\CoreBundle\Bundle\Helper\BootstrapHelper;
use Zikula\Bundle\CoreBundle\Bundle\Scanner;
use Zikula\ExtensionsModule\Helper\ComposerValidationHelper;
use Zikula\ThemeModule\Entity\Repository\ThemeEntityRepository;
use Zikula\ThemeModule\Entity\ThemeEntity;

/**
 * Helper functions for the theme module
 */
class BundleSyncHelper
{
    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var ThemeEntityRepository
     */
    private $themeEntityRepository;

    /**
     * @var BootstrapHelper
     */
    private $bootstrapHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ComposerValidationHelper
     */
    private $composerValidationHelper;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * BundleSyncHelper constructor.
     *
     * @param ZikulaHttpKernelInterface $kernel
     * @param ThemeEntityRepository $themeEntityRepository
     * @param BootstrapHelper $bootstrapHelper
     * @param TranslatorInterface $translator
     * @param ComposerValidationHelper $composerValidationHelper
     * @param SessionInterface $session
     */
    public function __construct(
        ZikulaHttpKernelInterface $kernel,
        ThemeEntityRepository $themeEntityRepository,
        BootstrapHelper $bootstrapHelper,
        TranslatorInterface $translator,
        ComposerValidationHelper $composerValidationHelper,
        SessionInterface $session
    ) {
        $this->kernel = $kernel;
        $this->themeEntityRepository = $themeEntityRepository;
        $this->bootstrapHelper = $bootstrapHelper;
        $this->translator = $translator;
        $this->composerValidationHelper = $composerValidationHelper;
        $this->session = $session;
    }

    /**
     * Regenerates the theme list
     * @return bool true
     * @throws \Exception
     */
    public function regenerate()
    {
        // sync the filesystem and the bundles table
        $this->bootstrapHelper->load();

        // Get all themes on filesystem
        $bundleThemes = [];

        $scanner = new Scanner();
        $scanner->scan(['themes']);
        $newThemes = $scanner->getThemesMetaData();

        /** @var \Zikula\Bundle\CoreBundle\Bundle\MetaData $themeMetaData */
        foreach ($newThemes as $name => $themeMetaData) {
            foreach ($themeMetaData->getPsr4() as $ns => $path) {
                $this->kernel->getAutoloader()->addPsr4($ns, $path);
            }

            $bundleClass = $themeMetaData->getClass();

            /** @var $bundle \Zikula\ThemeModule\AbstractTheme */
            $bundle = new $bundleClass();
            $themeMetaData->setTranslator($this->translator);
            $themeVersionArray = $themeMetaData->getThemeFilteredVersionInfoArray();

            // set defaults for all themes
            $themeVersionArray['type'] = 3;
            $themeVersionArray['state'] = ThemeEntityRepository::STATE_INACTIVE;
            $themeVersionArray['contact'] = 3;

            $finder = new Finder();
            $finder->files()->in($bundle->getPath())->depth(0)->name('composer.json');
            foreach ($finder as $splFileInfo) {
                // there will only be one loop here
                $this->composerValidationHelper->check($splFileInfo);
                if ($this->composerValidationHelper->isValid()) {
                    $bundleThemes[$bundle->getName()] = $themeVersionArray;
                } else {
                    $this->session->getFlashBag()->add('error', $this->translator->__f('Cannot load %extension because the composer file is invalid.', ['%extension' => $bundle->getName()]));
                    foreach ($this->composerValidationHelper->getErrors() as $error) {
                        $this->session->getFlashBag()->add('error', $error);
                    }
                }
            }
        }

        /**
         * Persist themes
         */
        $dbthemes = [];
        $themeEntities = $this->themeEntityRepository->findAll();
        foreach ($themeEntities as $entity) {
            $entity = $entity->toArray();
            $dbthemes[$entity['name']] = $entity;
        }

        // See if we have lost any themes since last generation
        foreach ($dbthemes as $name => $themeinfo) {
            if (empty($bundleThemes[$name])) {
                // delete item from db
                $item = $this->themeEntityRepository->findOneBy(['name' => $name]);
                $this->themeEntityRepository->removeAndFlush($item);

                unset($dbthemes[$name]);
            }
        }

        // See if we have gained any themes since last generation,
        // or if any current themes have been upgraded
        foreach ($bundleThemes as $name => $themeinfo) {
            if (empty($dbthemes[$name])) {
                // add item to db
                $item = new ThemeEntity();
                $item->merge($themeinfo);
                $this->themeEntityRepository->persistAndFlush($item);
            }
        }

        // see if any themes have changed
        foreach ($bundleThemes as $name => $themeinfo) {
            if (isset($dbthemes[$name])) {
                if (($themeinfo['type'] != $dbthemes[$name]['type'])
                    || ($themeinfo['description'] != $dbthemes[$name]['description'])
                    || ($themeinfo['version'] != $dbthemes[$name]['version'])
                    || ($themeinfo['admin'] != $dbthemes[$name]['admin'])
                    || ($themeinfo['user'] != $dbthemes[$name]['user'])
                    || ($themeinfo['system'] != $dbthemes[$name]['system'])
                    || ($themeinfo['contact'] != $dbthemes[$name]['contact'])
                    || ($themeinfo['xhtml'] != $dbthemes[$name]['xhtml'])
                ) {
                    $themeinfo['id'] = $dbthemes[$name]['id'];
                    // update item
                    /** @var $item ThemeEntity */
                    $item = $this->themeEntityRepository->find($themeinfo['id']);
                    $item->merge($themeinfo);
                    $this->themeEntityRepository->persistAndFlush($item);
                }
            }
        }

        return true;
    }
}
