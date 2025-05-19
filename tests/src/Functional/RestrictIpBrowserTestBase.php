<?php

namespace Drupal\Tests\restrict_ip\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides some helper functions for functional tests.
 *
 * @group restrict_ip
 */
abstract class RestrictIpBrowserTestBase extends BrowserTestBase {

  /**
   * Status code helper method.
   */
  public function assertStatusCodeEquals($statusCode) {
    $this->assertSession()->statusCodeEquals($statusCode);
  }

  /**
   * Element assertion helper method.
   */
  public function assertElementExists($selector) {
    $this->assertSession()->elementExists('css', $selector);
  }

  /**
   * Element attribute existence helper method.
   */
  public function assertElementAttributeExists($selector, $attribute) {
    $this->assertSession()->elementAttributeExists('css', $selector, $attribute);
  }

  /**
   * Checks that an attribute of a specific elements contains text.
   */
  public function assertElementAttributeContains($selector, $attribute, $value) {
    $this->assertSession()->elementAttributeContains('css', $selector, $attribute, $value);
  }

  /**
   * A radio selection helper method.
   */
  public function selectRadio($htmlID) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $radio = $this->getSession()->getPage()->findField($htmlID);
    $name = $radio->getAttribute('name');
    $option = $radio->getAttribute('value');
    $this->getSession()->getPage()->selectFieldOption($name, $option);
  }

  /**
   * A radio assertion helper method.
   */
  public function assertRadioSelected($htmlID) {
    if (!preg_match('/^#/', $htmlID)) {
      $htmlID = '#' . $htmlID;
    }

    $selected_radio = $this->getSession()->getPage()->find('css', 'input[type="radio"]:checked' . $htmlID);

    if (!$selected_radio) {
      throw new \Exception('Radio button with ID ' . $htmlID . ' is not selected');
    }
  }

  /**
   * Check box selection helper method.
   */
  public function checkCheckbox($htmlID) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->getSession()->getPage()->checkField($htmlID);
  }

  /**
   * Check box checked helper method.
   */
  public function assertCheckboxChecked($htmlID) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->assertSession()->checkboxChecked($htmlID);
  }

  /**
   * Text value filling helper method.
   */
  public function fillTextValue($htmlID, $value) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->getSession()->getPage()->fillField($htmlID, $value);
  }

  /**
   * Text value assertion helper method.
   */
  public function assertTextValue($htmlID, $value) {
    if (preg_match('/^#/', $htmlID)) {
      $htmlID = substr($htmlID, 1);
    }

    $this->assertSession()->fieldValueEquals($htmlID, $value);
  }

}
