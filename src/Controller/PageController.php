<?php

namespace Drupal\restrict_ip\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The page controller.
 */
class PageController extends ControllerBase implements PageControllerInterface {

  /**
   * {@inheritdoc}
   */
  public function configPage(): array {
    return [
      '#prefix' => '<div id="restrict_ip_config_page">',
      '#suffix' => '</div.',
      'form' => $this->formBuilder()->getForm('\Drupal\restrict_ip\Form\ConfigForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function accessDeniedPage(): RedirectResponse|array {
    if (!isset($_SESSION['restrict_ip']) || !$_SESSION['restrict_ip']) {
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    $config = $this->config('restrict_ip.settings');
    $page['access_denied'] = [
      '#markup' => $this->t('Você não está autorizado a acessar esta página.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $contact_mail = $config->get('mail_address');
    if (strlen($contact_mail)) {
      $contact_mail = str_replace('@', '[at]', $contact_mail);
      $mail_markup = new FormattableMarkup('<span id="restrict_ip_contact_mail">@address</span>', ['@address' => $contact_mail]);
      $page['contact_us'] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('If you feel this is in error, please contact an administrator at @email.', ['@email' => $mail_markup]),
        '#attached' => [
          'library' => [
            'restrict_ip/mail_fixer',
          ],
        ],
      ];
    }

    if ($config->get('allow_role_bypass')) {
      if ($this->currentUser()->isAuthenticated()) {
        $url = Url::fromRoute('user.logout');
        $link = Link::fromTextAndUrl($this->t('Logout'), $url);
        $page['logout_link'] = [
          '#markup' => $link->toString(),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
      elseif ($config->get('bypass_action') === 'provide_link_login_page') {
        $url = Url::fromRoute('user.login');
        $link = Link::fromTextAndUrl($this->t('Sign in'), $url);
        $page['login_link'] = [
          '#markup' => $link->toString(),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
    }

    $this->moduleHandler()->alter('restrict_ip_access_denied_page', $page);

    return $page;
  }

}
