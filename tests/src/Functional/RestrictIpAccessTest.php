<?php

namespace Drupal\Tests\restrict_ip\Functional;

/**
 * Restrict ip access test class.
 *
 * @group restrict_ip
 */
class RestrictIpAccessTest extends RestrictIpBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'restrict_ip',
    'node',
    'block',
  ];

  /**
   * Test that a user is blocked when the module is enabled.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testModuleEnabled() {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);
    $this->checkCheckbox('#edit-enable');
    $this->click('#edit-submit');
    $this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');
  }

  /**
   * Test that a user is not blocked if their IP address is whitelisted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testIpWhitelist() {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);

    $this->checkCheckbox('#edit-enable');
    $this->fillTextValue('edit-address-list', $_SERVER['REMOTE_ADDR'] . PHP_EOL . '::1');
    $this->click('#edit-submit');
    $this->assertSession()->pageTextNotContains('The page you are trying to access cannot be accessed from your IP address.');
  }

  /**
   * Tests if the email address can be entered.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testEmailAddressDisplays(): void {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);
    $this->checkCheckbox('#edit-enable');
    $this->fillTextValue('edit-mail-address', 'dave@example.com');
    $this->click('#edit-submit');

    $this->assertSession()->pageTextContains('dave[at]example.com');
  }

  /**
   * Tests if access can be bypassed with the right permission.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAccessBypassByRole(): void {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->createArticleContentType();
    $this->createArticle();

    $this->drupalLogin($adminUser);

    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);
    $this->checkCheckbox('#edit-allow-role-bypass');
    $this->click('#edit-submit');

    $admin_role = $this->createRole(['bypass ip restriction']);
    $adminUser->addRole($admin_role)->save();
    $this->checkCheckbox('#edit-enable');
    $this->click('#edit-submit');

    $this->drupalLogout();
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');
    $this->assertSession()->linkExists('Sign in');

    $this->drupalGet('user');
    $this->assertStatusCodeEquals(200);
    $this->drupalGet('user/login');
    $this->assertStatusCodeEquals(200);
    $this->drupalGet('user/password');
    $this->assertStatusCodeEquals(200);
    $this->drupalGet('user/register');
    $this->assertStatusCodeEquals(200);
    $this->drupalGet('user/reset/1');
    $this->assertStatusCodeEquals(403);
  }

  /**
   * Tests if redirect with the right permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRedirectToLoginWhenBypassByRoleEnabled(): void {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->createArticleContentType();
    $this->createArticle();

    $this->drupalLogin($adminUser);

    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);
    $this->checkCheckbox('#edit-allow-role-bypass');
    $this->selectRadio('#edit-bypass-action-redirect-login-page');
    $this->click('#edit-submit');

    $admin_role = $this->createRole(['bypass ip restriction']);
    $adminUser->addRole($admin_role)->save();
    $this->checkCheckbox('#edit-enable');
    $this->click('#edit-submit');

    $this->drupalLogout();
    $this->drupalGet('node/1');
    $this->assertElementExists('#edit-name');
  }

  /**
   * Tests whitelisting paths.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testWhitelistedPaths(): void {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->drupalLogin($adminUser);

    $this->createArticleContentType();
    $this->createArticle();
    $this->createArticle();

    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);
    $this->checkCheckbox('#edit-enable');
    $this->selectRadio('#edit-white-black-list-1');
    $this->fillTextValue('edit-page-whitelist', 'node/1');
    $this->click('#edit-submit');

    $this->drupalGet('node/1');
    $this->assertSession()->pageTextNotContains('The page you are trying to access cannot be accessed from your IP address.');

    $this->drupalGet('node/2');
    $this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');
  }

  /**
   * Tests blacklisting paths.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBlacklistedPaths(): void {
    $adminUser = $this->drupalCreateUser([
      'administer restricted ip addresses',
      'access administration pages',
      'administer modules',
    ]);

    $this->drupalLogin($adminUser);

    $this->createArticleContentType();
    $this->createArticle();
    $this->createArticle();

    $this->drupalGet('admin/config/people/restrict_ip');
    $this->assertStatusCodeEquals(200);
    $this->checkCheckbox('#edit-enable');
    $this->selectRadio('#edit-white-black-list-2');
    $this->fillTextValue('edit-page-blacklist', 'node/1');
    $this->click('#edit-submit');

    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');

    $this->drupalGet('node/2');
    $this->assertSession()->pageTextNotContains('The page you are trying to access cannot be accessed from your IP address.');
  }

  /**
   * Helper function to create an article content type.
   */
  private function createArticleContentType(): void {
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);

    $type->save();
  }

  /**
   * Helper function to create an article.
   */
  private function createArticle(): void {
    static $counter;

    if (!$counter) {
      $counter = 1;
    }

    $node = $this->container->get('entity_type.manager')->getStorage('node')
      ->create([
        'type' => 'article',
        'title' => 'Article ' . $counter,
      ]);

    $node->save();
    $this->container->get('router.builder')->rebuild();
    $counter += 1;
  }

}
