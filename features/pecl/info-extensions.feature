Feature: download and install PECL extensions
  In order to install PECL extensions
  As a pickle user
  I should be able to download and install PECL extensions

  Scenario Outline: Show info about downloaded extensions
    Given I run "pickle info <extension>@<version>"
    Then it should pass
    And the output should contain:
      """
      - Installing <extension> (<version>)
      """
    And the output should contain:
      """
      Package name                      | <pretty>
      """
    And the output should contain:
      """
      Package version (current release) | <version>
      """

    Examples:
      | extension | pretty    | version  |
      | apc       | APC       | 3.1.13   |
      | apcu      | apcu      | 4.0.6    |
      | mongo     | mongo     | 1.5.4    |
      | memcache  | memcache  | 3.0.8    |
      | amqp      | amqp      | 1.4.0    |

  Scenario: Show informations about a PECL extension's options
    When I run "pickle info apc@3.1.13"
    Then it should pass
    And the output should contain:
      """
      | enable | whether to enable APC support            | no      |
      """
    And the output should contain:
      """
      | enable | Disable pthread mutex locking            | yes     |
      """

    When I run "pickle info oci8@2.0.8"
    Then it should pass
    And the output should contain:
      """
      | with | for Oracle Database OCI8 support |         |
      """

  Scenario Outline: Override an extensions version
    When I run "pickle info --version-override=1.0.0 <extension>"
    Then it should pass
    And the output should contain:
      """
      Package version (current release) | 1.0.0
      """
    Examples:
      | extension |
      | apcu      |
      | sqlsrv    |
      | swoole    |
      | yaml      |
