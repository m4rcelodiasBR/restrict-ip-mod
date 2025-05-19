<?php

namespace Drupal\restrict_ip\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The page controller interface.
 */
interface PageControllerInterface {

  /**
   * Provides the configuration page for the Restrict IP module.
   */
  public function configPage(): array;

  /**
   * Provides the Access Denied page for the Restrict IP module.
   */
  public function accessDeniedPage(): RedirectResponse|array;

}
