Feature: convert package.xml to RELEASE files
  In order to use pickle on my package
  As an extension developer
  I should be able to create RELEASE files from my package.xml

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
          <changelog>
              <release>
                  <date>2013-01-02</date>
                  <version>
                      <release>3.1.14</release>
                      <api>3.1.0</api>
                  </version>
                  <stability>
                      <release>beta</release>
                      <api>stable</api>
                  </stability>
                  <license uri="http://www.php.net/license">PHP License</license>
                  <notes>This is a note</notes>
              </release>
              <release>
                  <date>2012-09-03</date>
                  <version>
                      <release>3.1.13</release>
                      <api>3.1.0</api>
                  </version>
                  <stability>
                      <release>beta</release>
                      <api>stable</api>
                  </stability>
                  <license uri="http://www.php.net/license">PHP License</license>
                  <notes>This is a note</notes>
              </release>
          </changelog>
      </package>
      """

  Scenario: Create RELEASE file for current release
    When I run "pickle convert"
    Then it should pass with:
      """
      Successfully converted dummy
      +-----------------------------------+----------------+
      | Package name                      | dummy          |
      | Package version (current release) | 3.1.15         |
      | Package status                    | beta           |
      | Previous release(s)               | 3.1.13, 3.1.14 |
      +-----------------------------------+----------------+
      """
    And "RELEASE-3.1.15" file should contain:
      """
      Date:             2013-??-??
      Package version:  3.1.15
      Package state:    beta
      API Version:      3.1.0
      API state:        stable

      Changelog:
      This is a note
      """

  Scenario: Create RELEASE file for each release in changelog
    When I run "pickle convert"
    Then it should pass with:
      """
      uccessfully converted dummy
      +-----------------------------------+----------------+
      | Package name                      | dummy          |
      | Package version (current release) | 3.1.15         |
      | Package status                    | beta           |
      | Previous release(s)               | 3.1.13, 3.1.14 |
      +-----------------------------------+----------------+
      """
    And "RELEASE-3.1.15" file should exist
    And "RELEASE-3.1.14" file should contain:
      """
      Date:             2013-01-02
      Package version:  3.1.14
      Package state:    beta
      API Version:      3.1.0
      API state:        stable

      Changelog:
      This is a note
      """
    And "RELEASE-3.1.13" file should contain:
      """
      Date:             2012-09-03
      Package version:  3.1.13
      Package state:    beta
      API Version:      3.1.0
      API state:        stable

      Changelog:
      This is a note
      """