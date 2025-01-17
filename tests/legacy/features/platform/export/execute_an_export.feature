@javascript
Feature: Execute a job
  In order to launch an export
  As a product manager
  I need to be able to execute a valid export

  Background:
    Given a "footwear" catalog configuration
    And I am logged in as "Julia"

  Scenario: Fail to see the execute button of a job with validation errors
    Given the following job "csv_footwear_product_export" configuration:
      | filePath |  |
    When I am on the "csv_footwear_product_export" export job page
    Then I should not see the "Export now" link

  Scenario: Fail to launch a job with validation errors
    Given the following job "csv_footwear_product_export" configuration:
      | filePath |  |
    And I am on the "csv_footwear_product_export" export job page
    When I launch the "csv_footwear_product_export" export job
    Then I should not see the text "The export is running."
    And I should not see the text "An error occured during the export execution."

  Scenario: Successfully launch a valid job
    Given the following product:
      | sku       | family | categories        | name-en_US | price          | size | color |
      | boots-001 | boots  | winter_collection | Boots 1    | 20 EUR, 25 USD | 40   | black |
    Given the following job "csv_footwear_product_export" configuration:
      | filePath | %tmp%/product_export/product_export.csv |
    And I am on the "csv_footwear_product_export" export job page
    When I launch the "csv_footwear_product_export" export job
    And I wait for the "csv_footwear_product_export" job to finish
    Then I should see the text "Execution details"
    And file "%tmp%/product_export/product_export.csv" should exist
    And an email to "Julia@example.com" should have been sent
