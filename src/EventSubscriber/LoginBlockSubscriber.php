<?php

namespace Drupal\helperbox\EventSubscriber;

use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Blocks access to /user/login and /user/password paths.
 */
class LoginBlockSubscriber implements EventSubscriberInterface {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new LoginBlockSubscriber.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run later in the request cycle when session is initialized.
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

  /**
   * Checks if the request is for the login page and blocks it.
   */
  public function onRequest(RequestEvent $event) {
    // Only act on main requests, not subrequests.
    if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
      return;
    }

    $request = $event->getRequest();
    $rawPath = ltrim($request->server->get('REQUEST_URI'), '/');
    // Remove query string if present.
    if ($pos = strpos($rawPath, '?')) {
      $rawPath = substr($rawPath, 0, $pos);
    }
    // Remove only the first 'sub_dir_name/' prefix.
    $rawPath = preg_replace('/^sub_dir_name\//', '', $rawPath, 1);
    // Remove trailing slash.
    $rawPath = rtrim($rawPath, '/');
    
    // Block /user if user is not authenticated.
    // Check session directly since currentUser may not be initialized yet at this priority.
    $session = $request->getSession();
    $isAuthenticated = $session && $session->has('uid') && $session->get('uid') > 0;
    if ($rawPath === 'user' && !$isAuthenticated) {
      throw new NotFoundHttpException();
    }

    // Block direct access to /user/login and /user/password.
    // Users must use URL aliases like /user-login instead.
    if ($rawPath === 'user/login' || $rawPath === 'user/password') {
      $alias = $this->aliasManager->getAliasByPath('/' . $rawPath);
      if ($alias != '/' . $rawPath) {
        throw new NotFoundHttpException();
      }
    }
  }
}
