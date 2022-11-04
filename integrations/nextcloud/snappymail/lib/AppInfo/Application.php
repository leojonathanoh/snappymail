<?php

namespace OCA\SnappyMail\AppInfo;

use OCA\SnappyMail\Util\SnappyMailHelper;
use OCA\SnappyMail\Controller\FetchController;
use OCA\SnappyMail\Controller\PageController;
use OCA\SnappyMail\Search\Provider;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\IL10N;
use OCP\IUser;
use OCP\User\Events\PostLoginEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;

class Application extends App implements IBootstrap
{
	public const APP_ID = 'snappymail';

	public function __construct(array $urlParams = [])
	{
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void
	{
		/**
		 * Controllers
		 */
		$context->registerService(
			'PageController', function($c) {
				return new PageController(
					$c->query('AppName'),
					$c->query('Request')
				);
			}
		);

		$context->registerService(
			'FetchController', function($c) {
				return new FetchController(
					$c->query('AppName'),
					$c->query('Request'),
					$c->getServer()->getAppManager(),
					$c->query('ServerContainer')->getConfig(),
					$c->query(IL10N::class)
				);
			}
		);

		/**
		 * Utils
		 */
		$context->registerService(
			'SnappyMailHelper', function($c) {
				return new SnappyMailHelper();
			}
		);

		$context->registerSearchProvider(Provider::class);
	}

	public function boot(IBootContext $context): void
	{
		if (!\is_dir(\rtrim(\trim(\OC::$server->getSystemConfig()->getValue('datadirectory', '')), '\\/') . '/appdata_snappymail')) {
			return;
		}
/*
		$container = $this->getContainer();
		$container->query('OCP\INavigationManager')->add(function () use ($container) {
			$urlGenerator = $container->query('OCP\IURLGenerator');
			return [
				'id' => 'snappymail',
				'order' => 4,
				'href' => $urlGenerator->linkToRoute('snappymail.page.index'),
				'icon' => $urlGenerator->imagePath('snappymail', 'logo-white-64x64.png'),
				'name' => \OCP\Util::getL10N('snappymail')->t('Email')
			];
		});
*/

		$dispatcher = $container->query('OCP\EventDispatcher\IEventDispatcher');
		$this->dispatcher->addListener(PostLoginEvent::class, function (PostLoginEvent $Event) {
			$config = \OC::$server->getConfig();
			// Only store the user's password in the current session if they have
			// enabled auto-login using Nextcloud username or email address.
			if ($config->getAppValue('snappymail', 'snappymail-autologin', false)
			 || $config->getAppValue('snappymail', 'snappymail-autologin-with-email', false)) {
				$sUID = $Event->getUser()->getUID();
				\OC::$server->getSession()['snappymail-nc-uid'] = $sUID;
				\OC::$server->getSession()['snappymail-password'] = SnappyMailHelper::encodePassword($Event->getPassword(), $sUID);
			}
		});

		$this->dispatcher->addListener(BeforeUserLoggedOutEvent::class, function (BeforeUserLoggedOutEvent $Event) {
			\OC::$server->getSession()['snappymail-password'] = '';
			SnappyMailHelper::loadApp();
			\RainLoop\Api::Actions()->Logout(true);
		});
/*
		// https://github.com/nextcloud/impersonate/issues/179
		// https://github.com/nextcloud/impersonate/pull/180
		$class = 'OCA\Impersonate\Events\ImpersonateEvent';
		if (\class_exists($class)) {
			$this->dispatcher->addListener($class, function ($Event) {
				\OC::$server->getSession()['snappymail-password'] = '';
				SnappyMailHelper::loadApp();
				\RainLoop\Api::Actions()->Logout(true);
			});
		}
		\OC::$server->getUserSession()->listen('\OC\User', 'impersonate', function($user, $newUser) {
			\OC::$server->getSession()['snappymail-password'] = '';
			SnappyMailHelper::loadApp();
			\RainLoop\Api::Actions()->Logout(true);
		});
*/
	}
}
