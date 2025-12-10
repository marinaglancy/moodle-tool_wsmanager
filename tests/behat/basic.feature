@tool @tool_wsmanager
Feature: Basic tests for Web service manager

  @javascript
  Scenario: I can browse functions in the Web service manager
    Given I log in as "admin"
    And I navigate to "Server > Web services > Web service manager" in site administration
    And I should see "auth_email_signup_user"
