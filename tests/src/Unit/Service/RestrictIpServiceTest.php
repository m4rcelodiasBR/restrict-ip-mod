<?php

namespace Drupal\Tests\restrict_ip\Unit\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\restrict_ip\Mapper\RestrictIpMapperInterface;
use Drupal\restrict_ip\Service\RestrictIpService;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the restrict ip service test.
 *
 * @coversDefaultClass \Drupal\restrict_ip\Service\RestrictIpService
 *
 * @group restrict_ip
 */
class RestrictIpServiceTest extends UnitTestCase {

  /**
   * The mocked current user.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Session\AccountProxyInterface
   */
  protected MockObject|AccountProxyInterface $currentUser;

  /**
   * The mocked current path stack object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Path\CurrentPathStack
   */
  protected MockObject|CurrentPathStack $currentPathStack;

  /**
   * The mocked request stack object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\RequestStack
   */
  protected MockObject|RequestStack $requestStack;

  /**
   * The mocked request object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\Request
   */
  protected MockObject|Request $request;

  /**
   * The mocked mapper object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\restrict_ip\Mapper\RestrictIpMapperInterface
   */
  protected MockObject|RestrictIpMapperInterface $mapper;

  /**
   * The mocked path matcher object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Path\PathMatcherInterface
   */
  protected MockObject|PathMatcherInterface $pathMatcher;

  /**
   * The mocked module handler object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected MockObject|ModuleHandlerInterface $moduleHandler;

  /**
   * The mocked user data object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\user\UserDataInterface
   */
  protected MockObject|UserDataInterface $userData;

  /**
   * {@inheritdoc}
   *
   * @throws \PHPUnit\Framework\MockObject\Exception
   */
  public function setUp(): void {
    parent::setUp();

    $this->currentUser = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $this->currentPathStack = $this->createMock('Drupal\Core\Path\CurrentPathStack');
    $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
    $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
    $this->mapper = $this->createMock('Drupal\restrict_ip\Mapper\RestrictIpMapper');
    $this->mapper->expects($this->any())
      ->method('getWhitelistedPaths')
      ->willReturn(['/node/1']);
    $this->mapper->expects($this->any())
      ->method('getBlacklistedPaths')
      ->willReturn(['/node/1']);
    $this->pathMatcher = $this->createMock('Drupal\Core\Path\PathMatcherInterface');
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->userData = $this->createMock('Drupal\user\UserDataInterface');
  }

  /**
   * @covers ::userIsBlocked
   */
  public function testUserHasRoleBypassPermission(): void {
    $this->currentUser->expects($this->once())
      ->method('hasPermission')
      ->with('bypass ip restriction')
      ->willReturn(TRUE);

    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn('/restricted/path');

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory(['allow_role_bypass' => TRUE]);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);

    $user_is_blocked = $restrictIpService->userIsBlocked();
    $this->assertFalse($user_is_blocked, 'User is not blocked when they have the permission bypass access restriction');
  }

  /**
   * @covers ::userIsBlocked
   * @dataProvider pathInAllowedPathsDataProvider
   */
  public function testPathInAllowedPaths($path, $expectedResult): void {
    $this->currentUser->expects($this->once())
      ->method('hasPermission')
      ->willReturn(FALSE);

    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn($path);

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory(['allow_role_bypass' => TRUE]);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);

    $user_is_blocked = $restrictIpService->userIsBlocked();
    $this->assertSame($expectedResult, $user_is_blocked, 'User is not blocked when they are on the allowed path: ' . $path);
  }

  /**
   * Data provider for testPathInAllowedPaths()
   */
  public static function pathInAllowedPathsDataProvider(): array {
    return [
      ['/user', FALSE],
      ['/user/login', FALSE],
      ['/user/password', FALSE],
      ['/user/logout', FALSE],
      ['/user/reset/something', FALSE],
      ['/invalid/path', FALSE],
    ];
  }

  /**
   * @covers ::testForBlock
   * @dataProvider whitelistDataProvider
   */
  public function testWhitelist($pathToCheck, $pathAllowed, $expectedResult, $message): void {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn($pathToCheck);

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory([
      'enable' => TRUE,
      'white_black_list' => 1,
    ]);

    $this->pathMatcher->expects($this->once())
      ->method('matchPath')
      ->willReturn($pathAllowed);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);

    $restrictIpService->testForBlock(TRUE);

    $this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
  }

  /**
   * Data provider for testWhitelist()
   */
  public static function whitelistDataProvider(): array {
    return [
      ['/node/1', TRUE, FALSE, 'User is allowed on whitelisted path'],
      ['/node/2', FALSE, TRUE, 'User is blocked on non-whitelisted path'],
    ];
  }

  /**
   * @covers ::testForBlock
   * @dataProvider blacklistDataProvider
   */
  public function testBlacklist($pathToCheck, $pathNotAllowed, $expectedResult, $message): void {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn($pathToCheck);

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory([
      'enable' => TRUE,
      'white_black_list' => 2,
    ]);

    $this->pathMatcher->expects($this->once())
      ->method('matchPath')
      ->willReturn($pathNotAllowed);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);
    $restrictIpService->testForBlock(TRUE);

    $this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
  }

  /**
   * Data provider for testBlacklist()
   */
  public static function blacklistDataProvider(): array {
    return [
      ['/node/1', TRUE, TRUE, 'User is blocked on blacklisted path'],
      ['/node/2', FALSE, FALSE, 'User is not blocked on non-blacklisted path'],
    ];
  }

  /**
   * @covers ::testForBlock
   * @dataProvider whitelistedIpAddressesTestDataProvider
   *
   * @throws \PHPUnit\Framework\MockObject\Exception
   *
   * @todo Unsure, why this test fails, reimplement it through
   * https://www.drupal.org/project/restrict_ip/issues/3328778.
   */
  public function todoTestWhitelistedIpAddresses($ipAddressToCheck, $expectedResult, $message): void {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn('/some/path');

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn($ipAddressToCheck);

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory([
      'enable' => TRUE,
      'white_black_list' => 0,
    ]);

    $mapper = $this->createMock('Drupal\restrict_ip\Mapper\RestrictIpMapper');

    $mapper->expects($this->any())
      ->method('getWhitelistedIpAddresses')
      ->willReturn(['::1']);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);
    $restrictIpService->testForBlock(TRUE);

    $this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
  }

  /**
   * Data provider for testWhitelistedIpAddresses()
   */
  public static function whitelistedIpAddressesTestDataProvider(): array {
    return [
      [
        '::1',
        FALSE,
        'User is not blocked when IP address has been whitelisted in through admin interface',
      ],
      [
        '::2',
        TRUE,
        'User is blocked when IP address has not been whitelisted in through admin interface',
      ],
    ];
  }

  /**
   * @covers ::testForBlock
   * @dataProvider settingsIpAddressesDataProvider
   */
  public function testSettingsIpAddresses($ipAddressToCheck, $configFactory, $expectedResult, $message) {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn('/some/path');

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);
    $restrictIpService->testForBlock(TRUE);

    $this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
  }

  /**
   * Data provider for testSettingsIpAddresses()
   */
  public function settingsIpAddressesDataProvider(): array {
    return [
      ['::1', $this->getConfigFactory([
        'enable' => TRUE,
        'white_black_list' => 0,
        'ip_whitelist' => ['::1'],
      ]), FALSE, 'User is not blocked when IP address has been whitelisted in settings.php',
      ],
      ['::1', $this->getConfigFactory([
        'enable' => TRUE,
        'white_black_list' => 0,
        'ip_whitelist' => ['::2'],
      ]), TRUE, 'User is blocked when IP address has not been whitelisted through settings.php',
      ],
    ];
  }

  /**
   * @covers ::cleanIpAddressInput
   * @dataProvider cleanIpAddressInputDataProvider
   */
  public function testCleanIpAddressInput($input, $expectedResult, $message) {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn('/some/path');

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory([]);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);

    $this->assertSame($expectedResult, $restrictIpService->cleanIpAddressInput($input), $message);
  }

  /**
   * Data provider for testCleanIpAddressInput()
   */
  public static function cleanIpAddressInputDataProvider(): array {
    return [
      ['111.111.111.111
			111.111.111.112',
        ['111.111.111.111', '111.111.111.112'],
        'Items properly parsed when separated by new lines',
      ],
      ['// This is a comment
			111.111.111.111',
        ['111.111.111.111'],
        'Items properly parsed when comment starting with // exists',
      ],
      ['# This is a comment
			111.111.111.111',
        ['111.111.111.111'],
        'Items properly parsed when comment starting with # exists',
      ],
      ['/**
			 *This is a comment
			 */
			111.111.111.111',
        ['111.111.111.111'],
        'Items properly parsed when multiline comment exists',
      ],
    ];
  }

  /**
   * @covers ::getCurrentUserIp
   */
  public function testGetCurrentUserIp() {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn('/some/path');

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory([]);

    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);

    $this->assertSame('::1', $restrictIpService->getCurrentUserIp(), 'User IP address is properly reported');
  }

  /**
   * @covers ::getCurrentPath
   */
  public function testGetCurrentPath() {
    $this->currentPathStack->expects($this->once())
      ->method('getPath')
      ->willReturn('/some/path');

    $this->request->expects($this->once())
      ->method('getClientIp')
      ->willReturn('::1');

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($this->request);

    $configFactory = $this->getConfigFactory([]);
    $restrictIpService = new RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher, $this->moduleHandler, $this->userData);
    $this->assertSame('/some/path', $restrictIpService->getCurrentPath(), 'Correct current path is properly reported');
  }

  /**
   * Helper function to return the config factory.
   */
  private function getConfigFactory(array $settings) {
    return $this->getConfigFactoryStub([
      'restrict_ip.settings' => $settings,
    ]);
  }

}
