<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\SearchModule\Container;

use ModUtil;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\PermissionsModule\Api\PermissionApi;
use Zikula\UsersModule\Api\CurrentUserApi;

class LinkContainer implements LinkContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PermissionApi
     */
    private $permissionApi;

    /**
     * @var CurrentUserApi
     */
    private $currentUserApi;

    /**
     * LinkContainer constructor.
     *
     * @param TranslatorInterface $translator     TranslatorInterface service instance
     * @param RouterInterface     $router         RouterInterface service instance
     * @param PermissionApi       $permissionApi  PermissionApi service instance
     * @param CurrentUserApi      $currentUserApi CurrentUserApi service instance
     */
    public function __construct(TranslatorInterface $translator, RouterInterface $router, PermissionApi $permissionApi, CurrentUserApi $currentUserApi)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->permissionApi = $permissionApi;
        $this->currentUserApi = $currentUserApi;
    }

    /**
     * get Links of any type for this extension
     * required by the interface
     *
     * @param string $type
     * @return array
     */
    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $method = 'get' . ucfirst(strtolower($type));
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return [];
    }

    /**
     * get the Admin links for this extension
     *
     * @return array
     */
    private function getAdmin()
    {
        $links = [];

        $links[] = [
            'url' => $this->router->generate('zikulasearchmodule_user_form'),
            'text' => $this->translator->__('Frontend'),
            'icon' => 'search'
        ];

        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_ADMIN)) {
            $links[] = [
                'url' => $this->router->generate('zikulasearchmodule_config_config'),
                'text' => $this->translator->__('Settings'),
                'icon' => 'wrench'
            ];
        }

        return $links;
    }

    /**
     * get the User links for this extension
     *
     * @return array
     */
    private function getUser()
    {
        $links = [];

        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_ADMIN)) {
            $links[] = [
                'url' => $this->router->generate('zikulasearchmodule_config_config'),
                'text' => $this->translator->__('Backend'),
                'icon' => 'wrench'
            ];
        }

        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_READ)) {
            $links[] = [
                'url' => $this->router->generate('zikulasearchmodule_user_form'),
                'text' => $this->translator->__('New search'),
                'icon' => 'search'
            ];
            if ($this->currentUserApi->isLoggedIn()) {
                $searchModules = ModUtil::apiFunc($this->getBundleName(), 'user', 'getallplugins');
                if (count($searchModules) > 0) {
                    $links[] = [
                        'url' => $this->router->generate('zikulasearchmodule_user_recent'),
                        'text' => $this->translator->__('Recent searches list'),
                        'icon' => 'list'
                    ];
                }
            }
        }

        return $links;
    }

    /**
     * set the BundleName as required by the interface
     *
     * @return string
     */
    public function getBundleName()
    {
        return 'ZikulaSearchModule';
    }
}