<?php

namespace Drupal\restrict_ip\Access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An IP permissions helper class.
 */
class RestrictIpPermissions implements RestrictIpPermissionsInterface, ContainerInjectionInterface {

  /**
   * Constructs the PageController object for the Restrict IP module.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function permissions(): array {
    $permissions = [];

    if ($this->configFactory->get('restrict_ip.settings')->get('allow_role_bypass')) {
      $permissions['bypass ip restriction'] = [
        'title' => 'Bypass IP Restriction',
        'description' => 'Allows the user to access the site even if not in the IP whitelist',
      ];
    }

    return $permissions;
  }

}
