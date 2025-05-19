<?php

namespace Drupal\restrict_ip\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\restrict_ip\Service\RestrictIpServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The event subscriber for ip restriction events.
 */
class RestrictIpEventSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * Creates an instance of the RestrictIpEventSubscriber class.
   *
   * @param \Drupal\restrict_ip\Service\RestrictIpServiceInterface $restrictIpService
   *   The restrict IP service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The Logger Factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Module Handler service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The Url Generator service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service object.
   */
  public function __construct(
    protected RestrictIpServiceInterface $restrictIpService,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected UrlGeneratorInterface $urlGenerator,
    protected Messenger $messenger,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('restrict_ip.service'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('module_handler'),
      $container->get('url_generator'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // On page load, we need to check for whether the user should be blocked by
    // IP:
    $events[KernelEvents::REQUEST][] = ['checkIpRestricted'];
    return $events;
  }

  /**
   * Check if response needs to be restricted.
   */
  public function checkIpRestricted(RequestEvent $event): void {
    unset($_SESSION['restrict_ip']);

    $this->restrictIpService->testForBlock();
    $config = $this->configFactory->get('restrict_ip.settings');
    if ($this->restrictIpService->userIsBlocked()) {
      if ($this->restrictIpService->getCurrentPath() != '/restrict_ip/access_denied') {
        if ($this->moduleHandler->moduleExists('dblog') && $config->get('dblog')) {
          $this->loggerFactory->get('Restrict IP')->warning('Access to the path %path was blocked for the IP address %ip_address', [
            '%path' => $this->restrictIpService->getCurrentPath(),
            '%ip_address' => $this->restrictIpService->getCurrentUserIp(),
          ]);
        }

        if ($config->get('allow_role_bypass') && $config->get('bypass_action') === 'redirect_login_page') {
          // Redirect the user to the change password page.
          $url = Url::fromRoute('user.login');
          $event->setResponse(new RedirectResponse($url->toString()));
        }
        elseif (in_array($config->get('white_black_list'), [0, 1])) {
          $url = Url::fromRoute('restrict_ip.access_denied_page');
          $event->setResponse(new RedirectResponse($url->toString()));
        }
        else {
                  
          //$this->setMessage($this->t('The page you are trying to access cannot be accessed from your IP address.'));
          //$url = $this->urlGenerator->generateFromRoute('<front>');
          //$event->setResponse(new RedirectResponse($url));

          //Trecho alterado por 2SG-PD Marcelo Dias. Sendo necessário que a página com a URL abaixo (ou o node correspondente) a ser exibida esteja criada no site. Altere esta linha caso queria outro endereço ou página.
          $customPath = '/acesso-restrito';
          // Aqui não alterar
          $url = Url::fromUri('internal:' . $customPath)->toString();
          $event->setResponse(new RedirectResponse($url));
        }
      }
    }
  }

  /**
   * Helper function to create a status message.
   */
  private function setMessage($message): void {
    $this->messenger->addStatus($message);
  }

}
