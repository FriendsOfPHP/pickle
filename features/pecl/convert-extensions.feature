Feature: convert PECL extensions
  In order to use pickle on PECL extensions
  As a pickle user
  I should be able to convert PECL extensions to pickle packages

  Scenario Outline: Convert downloaded extensions
    Given "<extension>" <version> extension exists
    When I run "pickle convert <extension>-<version>"
    Then it should pass with:
      """
      Successfully converted <extension>
      """
    And the output should contain:
      """
      Package name                      | <extension>
      """
    And the output should contain:
      """
      Package version (current release) | <version>
      """

    Examples:
      | extension | version  |
      | APC       | 3.1.13   |
      | apcu      | 4.0.6    |
      | mongo     | 1.5.4    |
      | memcache  | 3.0.8    |
      | imagick   | 3.2.0RC1 |
      | amqp      | 1.4.0    |

