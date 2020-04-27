@webUI @insulated @disablePreviews
Feature: Hide file/folders

  As a user
  I would like to display hidden files/folders
  So that I can choose to see the files that I want.

  Background:
    Given user "user1" has been created with default attributes and without skeleton files
    And user "user1" has logged in using the webUI
    And the user has browsed to the files page

  @smokeTest @TestAlsoOnExternalUserBackend
  Scenario: create a hidden folder
    When the user creates a folder with the name ".xyz" using the webUI
    Then folder ".xyz" should not be listed on the webUI
    When the user enables the setting to view hidden folders on the webUI
    Then folder ".xyz" should be listed on the webUI

  Scenario: create a hidden file
    Given user "user1" has uploaded file with content "some content" to "/lorem.txt"
    And the user has browsed to the files page
    When the user renames file "lorem.txt" to ".lorem.txt" using the webUI
    Then file ".lorem.txt" should not be listed on the webUI
    When the user enables the setting to view hidden files on the webUI
    Then folder ".lorem.txt" should be listed on the webUI

  Scenario: Delete and restore a hidden file from trashbin
    Given user "user1" has uploaded file with content "some content" to "/lorem.txt"
    And the user has browsed to the files page
    When the user renames file "lorem.txt" to ".lorem.txt" using the webUI
    And the user enables the setting to view hidden files on the webUI
    And the user deletes file ".lorem.txt" using the webUI
    Then file ".lorem.txt" should be listed in the trashbin on the webUI
    When the user restores file ".lorem.txt" from the trashbin using the webUI
    And the user browses to the files page
    Then file ".lorem.txt" should be listed on the webUI
