<?php


namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class FirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        /**
         * Go to the product page with a known sku
         */
        // $I->click(["xpath" => "//a[@data-product_sku='crocs']"]);
        $I->amOnPage('/croc.html');

        /**
         * Skip all cookie and warning popups
         */
//        $I->click('.ct-cookies-accept-button');
//        $I->click('.woocommerce-store-notice__dismiss-link');
//        $I->click('.single_add_to_cart_button');

        /**
         * Click on button to add to cart
         */
        $I->click('#product-addtocart-button');

        /**
         * Wait for 5 seconds because the page is making an Ajax call to the backend
         */
        $I->wait(5);

//        /**
//         * Scroll to the checkout button and wait a small moment
//         */
//        $I->scrollTo('.checkout-button', 0, 200);
//        $I->wait(0.5);

        /**
         * Go to the checkout
         */
        $I->click('.showcart');

        $I->click('.primary');

        $I->wait(5);

        /**
         * Fill the user information
         */
        $I->fillField('#customer-email', 'kevin.kroos@monta.nl');
        $I->fillField('firstname', 'Kevin');
        $I->fillField('lastname', 'Kroos');
        $I->fillField('company', 'Monta');
        $I->fillField('street[0]', 'Papland 16');
        $I->selectOption('country_id', 'Nederland');
        $I->fillField('city', 'Gorinchem');
        $I->fillField('postcode', '4206 CL');
        $I->fillField('telephone', '0613000842');

        $I->wait(10);

        $I->canSeeElement('.delivery-information');
        $I->canSeeElement('.montapacking-container-price');
        $I->canSeeElement('.nothavesameday');

        $I->scrollTo('.nothavesameday', 0, 200);
        $I->wait(0.5);
        $I->click('.nothavesameday');

        $I->wait(1);
        $I->scrollTo('#slider-content', 0, 200);
        $I->wait(1);
        $I->canSeeElement('#slider-content');
        $I->canSeeElement('.selected_day');
        $I->canSeeElement('.montapacking-tab');
        $I->canSeeElement('.montapacking-container-header');


        $I->click('.montapacking-styled-radiobutton');
        $I->click('.montapacking-tab-pickup');

        $I->wait(5);

//        $I->see('.montapacking-container-header');
//        $I->click('.pickup-information');

        $I->wait(5);

    }
}
