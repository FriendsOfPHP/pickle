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
      Downloading: 100%
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
      | xdebug    | xdebug    | 2.2.5    |
      | apc       | APC       | 3.1.13   |
      | apcu      | apcu      | 4.0.6    |
      | mongo     | mongo     | 1.5.4    |
      | memcache  | memcache  | 3.0.8    |
      | amqp      | amqp      | 1.4.0    |
      | redis     | redis     | 2.2.5    |
      | pthreads  | pthreads  | 2.0.7    |
