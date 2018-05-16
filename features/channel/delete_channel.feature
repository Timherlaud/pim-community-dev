@javascript
Feature: Delete a channel
  In order to manage channels for the catalog
  As an administrator
  I need to be able to delete channels

  Background:
    Given a "footwear" catalog configuration
    And I am logged in as "Peter"

  Scenario: Successfully delete a channel from the grid
    Given I am on the channels page
    And I should see channels Tablet and Mobile
    When I click on the "Delete" action of the row which contains "Tablet"
    And I confirm the deletion
    Then the grid should contain 1 element
    And I should not see channel Tablet

  Scenario: Successfully delete a channel
    Given I am on the "mobile" channel page
    When I press the secondary action "Delete"
    And I confirm the deletion
    Then the grid should contain 1 element
    And I should not see channel Mobile
