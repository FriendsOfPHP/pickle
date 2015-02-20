Feature: Install pickle package
  In order to use pickle on my package
  As an extension developer
  I should be able to install my extension from composer.json

  Background:
    Given a file named "composer.json" with:
      """
      {
          "name": "dummy",
          "description": "This is a dummy package",
          "version": "1.0.0",
          "type": "extension",
          "extra": {
              "configure-options": {
                  "enable-dummy": {
                      "default": "no",
                      "prompt": "Enable dummy support"
                  }
              }
          }
      }
      """
    And a file named "config.m4" with:
      """
      PHP_ARG_ENABLE(dummy, whether to enable dummy support,
      [  --enable-dummy           Enable dummy support])
      """

  Scenario: Install from CWD
    When I run "pickle install --dry-run"
    Then it should pass
