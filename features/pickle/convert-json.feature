Feature: convert package.xml to composer.json
  In order to use pickle on my package
  As an extension developer
  I should be able to create composer.json from my package.xml

  Background:
    Given a file named "package.xml" with:
      """
      <?xml version="1.0" encoding="UTF-8"?>
      <package packagerversion="1.4.7" version="2.0">
          <name>dummy</name>
          <providesextension>dummy</providesextension>
          <summary>This is a dummy package</summary>
          <version>
              <release>3.1.15</release>
              <api>3.1.0</api>
          </version>
          <stability>
              <release>beta</release>
              <api>stable</api>
          </stability>
          <date>2013-??-??</date>
          <notes>This is a note</notes>
          <changelog></changelog>
      </package>
      """

  Scenario: Search package.xml in CWD
    When I run "pickle convert"
    Then it should pass with:
      """
      Successfully converted dummy
      +-----------------------------------+--------+
      | Package name                      | dummy  |
      | Package version (current release) | 3.1.15 |
      | Package status                    | beta   |
      +-----------------------------------+--------+
      """
    And "composer.json" JSON file should contain:
      """
      {
          "name": "dummy",
          "type": "extension",
          "stability": "beta",
          "version": "3.1.15",
          "description": "This is a dummy package"
      }
      """

  Scenario: Search package.xml in the given path
    Given I am in the "empty-dir" path
    When I run "pickle convert ../"
    Then it should pass with:
      """
      Successfully converted dummy
      +-----------------------------------+--------+
      | Package name                      | dummy  |
      | Package version (current release) | 3.1.15 |
      | Package status                    | beta   |
      +-----------------------------------+--------+
      """
    And "../composer.json" JSON file should contain:
      """
      {
          "name": "dummy",
          "type": "extension",
          "stability": "beta",
          "version": "3.1.15",
          "description": "This is a dummy package"
      }
      """

  Scenario: Error if package.xml does not exist
    Given I am in the "empty-dir" path
    When I run "pickle convert"
    Then it should fail with:
      """
      The path '%%TEST_DIR%%/empty-dir' doesn't contain package.xml
      """

    Given I am in the ".." path
    When I run "pickle convert empty-dir"
    Then it should fail with:
      """
      The path 'empty-dir' doesn't contain package.xml
      """
