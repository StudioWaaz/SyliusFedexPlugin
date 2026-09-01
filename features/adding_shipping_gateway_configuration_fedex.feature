@managing_shipping_gateway_fedex
Feature: Creating shipping gateway
    In order to export shipping data to external shipping provider service
    As an Administrator
    I want to be able to add new shipping gateway with shipping method

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator
        And the store has "FedEx" shipping method with "$10.00" fee

    @ui
    Scenario: Creating FedEx shipping gateway
        When I visit the create shipping gateway configuration page for "fedex"
        And I select the "FedEx" shipping method
        And I fill the "FedEx API Key (Client ID)" field with "l7xx_sample_key"
        And I fill the "FedEx Secret Key" field with "sample_secret"
        And I fill the "FedEx Account Number" field with "123456789"
        And I fill the "Sender Contact Name" field with "John Doe"
        And I fill the "Sender Phone Number" field with "+33123456789"
        And I fill the "Sender Address" field with "10 Rue de la Paix"
        And I fill the "Sender City" field with "Paris"
        And I fill the "Sender Postal Code" field with "75002"
        And I add it
        Then I should be notified that the shipping gateway has been created
