Feature: download and install PECL extensions
  In order to install PECL extensions
  As a pickle user
  I should be able to download and install PECL extensions

  Scenario Outline: Install extensions from PECL repository
    Given I run "pickle install <extension> --dry-run"
    Then it should pass
    And the output should contain:
      """
      - Installing <extension> (latest-stable)
      """
    And the output should contain:
      """
      Package name                      | <pretty>
      """

    Examples:
      | extension | pretty    |
      | apc       | APC       |
      | apcu      | apcu      |
      | mongo     | mongo     |
      | memcache  | memcache  |
      | redis     | redis     |

  Scenario Outline: Install extensions from PECL repository with version constraint
    Given I run "pickle install <extension>@<version> --dry-run"
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
      | redis     | redis     | 2.2.5    |

  Scenario Outline: Install extensions from PECL names and stability flag
    Given I run "pickle install <extension>-<stability> --dry-run"
    Then it should pass
    And the output should contain:
      """
      - Installing <name> (latest-<stability>)
      """
    And the output should contain:
      """
      Package name                      | <pretty>
      """
    And the output should contain:
      """
      Package status                    | <stability>
      """

    Examples:
      | extension     | name      | pretty   | stability |
      | pecl/apc      | apc       | APC      | stable    |
      | apcu          | apcu      | apcu     | beta      |
      | pecl/memcache | memcache  | memcache | beta      |
