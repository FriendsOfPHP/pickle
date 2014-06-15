Feature: convert package.xml to information files
  In order to use pickle on my package
  As an extension developer
  I should be able to create information files from my package.xml

  Background:
    Given a file named "package.xml" with:
      """
      <?xml version="1.0" encoding="UTF-8"?>
      <package packagerversion="1.4.7" version="2.0">
          <name>dummy</name>
          <providesextension>dummy</providesextension>
          <summary>This is a dummy package</summary>
          <description>This is a dummy package description</description>
          <license uri="http://www.php.net/license">PHP License</license>
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
          <lead>
              <name>Rasmus Lerdorf</name>
              <user>rasmus</user>
              <email>rasmus@php.net</email>
              <active>yes</active>
          </lead>
          <developer>
              <name>Ilia Alshanetsky</name>
              <user>iliaa</user>
              <email>ilia@prohost.org</email>
              <active>no</active>
          </developer>
      </package>
      """

  Scenario: Create information files
    When I run "pickle convert"
    Then it should pass with:
      """
      Successfully converted dummy
      +-----------------------------------+--------+
      | Package name                      | dummy  |
      | Package version (current release) | 3.1.15 |
      | Package status                    | beta   |
      | Previous release(s)               |        |
      +-----------------------------------+--------+
      """
    And "CREDITS" file should contain:
      """
      Rasmus Lerdorf (rasmus) (rasmus@php.net) (yes)
      Ilia Alshanetsky (iliaa) (ilia@prohost.org) (no)
      """
    And "README" file should contain:
      """
      This is a dummy package

      This is a dummy package description
      """
    And "LICENSE" file should contain:
      """
      This package is under the following license(s):
      PHP License
      """