<?php

namespace Drupal\restrict_ip\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * Drush command to enable/disable restrict IP.
 */
final class RestrictIpCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Creates an instance of the RestrictIpCommands class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct();
  }

  /**
   * Disable/Enable restrict_ip.
   *
   * @param string $value
   *   Value enable or disable.
   *
   * @command restrict_ip:disable
   * @aliases ripd
   * @usage restrict_ip:disable off
   */
  public function ipRestrictDisable(string $value): void {
    $edit = $this->configFactory->getEditable('restrict_ip.settings');
    $edit->set('enable', $value === 'enable');
    $edit->save();
    $this->logger()->success($this->t('restrict_ip @value', [
      '@value' => $value,
    ]));
  }

}
