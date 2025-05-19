<?php

namespace Drupal\restrict_ip\Mapper;

/**
 * The interface for the ip mapping helper class.
 */
interface RestrictIpMapperInterface {

  /**
   * Retrieve a list of whitelisted IP addresses from the data source.
   *
   * @return array
   *   An array of whitelisted IP addresses
   */
  public function getWhitelistedIpAddresses(): array;

  /**
   * Save whitelisted IP addresses to the database.
   *
   * @param array $ip_addresses
   *   An array of IP addresses to be saved.
   * @param bool $overwriteExisting
   *   Indicator whether existing IP addresses should be deleted before saving.
   */
  public function saveWhitelistedIpAddresses(array $ip_addresses, bool $overwriteExisting = TRUE);

  /**
   * Retrieve a list of whitelisted paths from the data source.
   *
   * @return array
   *   An array of whitelisted paths
   */
  public function getWhitelistedPaths(): array;

  /**
   * Save paths to be whitelisted to the database.
   *
   * @param array $whitelistedPaths
   *   An array of paths to be saved for whitelisting.
   * @param bool $overwriteExisting
   *   Indicator whether existing whitelisted paths should be deleted before
   *   saving.
   */
  public function saveWhitelistedPaths(array $whitelistedPaths, bool $overwriteExisting = TRUE);

  /**
   * Retrieve a list of blacklisted paths from the data source.
   *
   * @return array
   *   An array of blacklisted paths
   */
  public function getBlacklistedPaths(): array;

  /**
   * Save paths to be blacklisted to the database.
   *
   * @param array $blacklistedPaths
   *   An array of paths to be saved for blacklisting.
   * @param bool $overwriteExisting
   *   Indicator whether existing blacklisted paths should be deleted before
   *   saving.
   */
  public function saveBlacklistedPaths(array $blacklistedPaths, bool $overwriteExisting = TRUE);

}
