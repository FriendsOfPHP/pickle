Feature: download and install extensions from git repository
  In order to install extensions
  As a pickle user
  I should be able to download and install extensions from git repositories

  Scenario Outline: Install extensions from git
    Given I run "pickle install <url> --dry-run"
    Then it should pass
    And the output should contain:
      """
      - Installing <extension> (master)
      """
    And the output should contain:
      """
      Cloning master
      """
    And the output should contain:
      """
      Package name                      | <extension>
      """

    Examples:
      | url                                 | extension |
      | git://github.com/krakjoe/apcu.git   | apcu      |
      | https://github.com/krakjoe/apcu.git | apcu      |

  Scenario Outline: Install extensions from git with version constraint
    Given I run "pickle install <url>#<version> --dry-run"
    Then it should pass
    And the output should contain:
      """
      - Installing <extension> (<version>)
      """
    And the output should contain:
      """
      Cloning <version>
      """
    And the output should contain:
      """
      Package name                      | <extension>
      """

    Examples:
      | url                                 | extension | version |
      | git://github.com/krakjoe/apcu.git   | apcu      | master  |
      | https://github.com/krakjoe/apcu.git | apcu      | v4.0.6  |

  Scenario Outline: Show info about downloaded extensions
    Given I run "pickle info <url>"
    Then it should pass
    And the output should contain:
      """
      - Installing <extension> (master)
      """
    And the output should contain:
      """
      Cloning master
      """
    And the output should contain:
      """
      Package name                      | <extension>
      """

    Examples:
      | url                                 | extension |
      | git://github.com/krakjoe/apcu.git   | apcu      |
      | https://github.com/krakjoe/apcu.git | apcu      |

  Scenario Outline: Show info about extensions with version constraint
    Given I run "pickle info <url>#<version>"
    Then it should pass
    And the output should contain:
      """
      - Installing <extension> (<version>)
      """
    And the output should contain:
      """
      Cloning <version>
      """
    And the output should contain:
      """
      Package name                      | <extension>
      """

    Examples:
      | url                                 | extension | version |
      | git://github.com/krakjoe/apcu.git   | apcu      | master  |
      | https://github.com/krakjoe/apcu.git | apcu      | v4.0.6  |
